<?php

namespace Tests\Unit\Models;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductChild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_combo_item_has_fillable_attributes(): void
    {
        $fillable = [
            'combo_id', 'product_id', 'product_child_id', 'quantity', 'unit_price',
        ];

        $item = new ComboItem();

        $this->assertEquals($fillable, $item->getFillable());
    }

    public function test_combo_item_casts_quantity_to_integer(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'quantity' => '5',
        ]);

        $this->assertIsInt($item->quantity);
        $this->assertEquals(5, $item->quantity);
    }

    public function test_combo_item_casts_unit_price_to_decimal(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'unit_price' => 25.50,
        ]);

        $this->assertEquals('25.50', $item->unit_price);
    }

    public function test_combo_item_can_be_created_with_factory(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(ComboItem::class, $item);
        $this->assertDatabaseHas('combo_items', [
            'id' => $item->id,
            'combo_id' => $combo->id,
        ]);
    }

    // Relationship tests

    public function test_combo_item_belongs_to_combo(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Combo::class, $item->combo);
        $this->assertEquals($combo->id, $item->combo->id);
    }

    public function test_combo_item_belongs_to_product(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(Product::class, $item->product);
        $this->assertEquals($product->id, $item->product->id);
    }

    public function test_combo_item_belongs_to_product_child(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        $child = ProductChild::factory()->create(['product_id' => $product->id]);
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => null,
            'product_child_id' => $child->id,
        ]);

        $this->assertInstanceOf(ProductChild::class, $item->productChild);
        $this->assertEquals($child->id, $item->productChild->id);
    }

    // Method tests

    public function test_get_product_name_returns_product_name(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);

        $this->assertEquals('Test Product', $item->getProductName());
    }

    public function test_get_product_name_returns_child_full_name(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create(['name' => 'Parent Product']);
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'Child Variant',
        ]);
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => null,
            'product_child_id' => $child->id,
        ]);

        $this->assertStringContainsString('Child Variant', $item->getProductName());
    }

    public function test_get_subtotal_calculates_correctly(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 25.00,
        ]);

        $this->assertEquals(75.00, $item->getSubtotal());
    }

    public function test_has_stock_returns_true_when_product_has_enough_stock(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create(['current_stock' => 10]);
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertTrue($item->hasStock());
    }

    public function test_has_stock_returns_false_when_product_has_insufficient_stock(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create(['current_stock' => 2]);
        
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->assertFalse($item->hasStock());
    }
}
