<?php

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_fillable_attributes(): void
    {
        $fillable = [
            'sku', 'barcode', 'name', 'description', 'category_id', 'subcategory_id',
            'brand_id', 'unit_id', 'tax_id', 'image', 'purchase_price',
            'sale_price', 'price_includes_tax', 'min_stock', 'max_stock',
            'current_stock', 'is_active',
        ];

        $product = new Product();

        $this->assertEquals($fillable, $product->getFillable());
    }

    public function test_product_casts_is_active_to_boolean(): void
    {
        $product = Product::factory()->create(['is_active' => 1]);

        $this->assertIsBool($product->is_active);
        $this->assertTrue($product->is_active);
    }

    public function test_product_casts_price_includes_tax_to_boolean(): void
    {
        $product = Product::factory()->create(['price_includes_tax' => 1]);

        $this->assertIsBool($product->price_includes_tax);
        $this->assertTrue($product->price_includes_tax);
    }

    public function test_product_can_be_created_with_factory(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
        ]);
    }

    public function test_inactive_product_factory_state(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->assertFalse($product->is_active);
    }

    // Relationship tests

    public function test_product_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_belongs_to_subcategory(): void
    {
        $category = Category::factory()->create();
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
        ]);

        $this->assertInstanceOf(Subcategory::class, $product->subcategory);
        $this->assertEquals($subcategory->id, $product->subcategory->id);
    }

    public function test_product_belongs_to_brand(): void
    {
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        $this->assertInstanceOf(Brand::class, $product->brand);
        $this->assertEquals($brand->id, $product->brand->id);
    }

    public function test_product_belongs_to_unit(): void
    {
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        $this->assertInstanceOf(Unit::class, $product->unit);
        $this->assertEquals($unit->id, $product->unit->id);
    }

    public function test_product_belongs_to_tax(): void
    {
        $tax = Tax::factory()->create();
        $product = Product::factory()->create(['tax_id' => $tax->id]);

        $this->assertInstanceOf(Tax::class, $product->tax);
        $this->assertEquals($tax->id, $product->tax->id);
    }

    public function test_product_has_many_children(): void
    {
        $product = Product::factory()->create();
        $child1 = ProductChild::factory()->create(['product_id' => $product->id]);
        $child2 = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $product->children);
        $this->assertCount(2, $product->children);
        $this->assertTrue($product->children->contains($child1));
        $this->assertTrue($product->children->contains($child2));
    }

    public function test_product_has_many_active_children(): void
    {
        $product = Product::factory()->create();
        $activeChild = ProductChild::factory()->create(['product_id' => $product->id, 'is_active' => true]);
        $inactiveChild = ProductChild::factory()->create(['product_id' => $product->id, 'is_active' => false]);

        $this->assertCount(1, $product->activeChildren);
        $this->assertTrue($product->activeChildren->contains($activeChild));
        $this->assertFalse($product->activeChildren->contains($inactiveChild));
    }

    // SKU Auto-Generation tests (Property 2)

    public function test_generate_sku_creates_unique_sku(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => null,
        ]);

        $sku = $product->generateSku();

        $this->assertNotNull($sku);
        $this->assertStringStartsWith('ELE-', $sku);
        $this->assertEquals($sku, $product->sku);
    }

    public function test_generate_sku_uses_prd_prefix_without_category(): void
    {
        // Create a product with a category first (required by DB)
        $product = Product::factory()->create(['sku' => null]);
        
        // Then simulate no category by clearing the relation
        $product->setRelation('category', null);
        $product->category_id = null;

        $sku = $product->generateSku();

        $this->assertStringStartsWith('PRD-', $sku);
    }

    public function test_generate_sku_pads_short_category_names(): void
    {
        $category = Category::factory()->create(['name' => 'AB']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => null,
        ]);

        $sku = $product->generateSku();

        $this->assertMatchesRegularExpression('/^ABX-\d{5}$/', $sku);
    }

    public function test_generate_sku_increments_for_same_category(): void
    {
        $category = Category::factory()->create(['name' => 'Test Category']);
        
        $product1 = Product::factory()->create(['category_id' => $category->id, 'sku' => null]);
        $sku1 = $product1->generateSku();
        $product1->save();

        $product2 = Product::factory()->create(['category_id' => $category->id, 'sku' => null]);
        $sku2 = $product2->generateSku();

        $this->assertNotEquals($sku1, $sku2);
        
        // Extract numbers and verify increment
        preg_match('/(\d+)$/', $sku1, $matches1);
        preg_match('/(\d+)$/', $sku2, $matches2);
        
        $this->assertEquals((int)$matches1[1] + 1, (int)$matches2[1]);
    }

    // Price Margin Calculation tests (Property 7)

    public function test_get_margin_calculates_correct_percentage(): void
    {
        $product = Product::factory()->create([
            'purchase_price' => 100.00,
            'sale_price' => 150.00,
        ]);

        $margin = $product->getMargin();

        $this->assertEquals(50.0, $margin);
    }

    public function test_get_margin_returns_null_for_zero_purchase_price(): void
    {
        $product = Product::factory()->zeroPurchasePrice()->create();

        $margin = $product->getMargin();

        $this->assertNull($margin);
    }

    public function test_get_margin_returns_negative_for_loss(): void
    {
        $product = Product::factory()->create([
            'purchase_price' => 100.00,
            'sale_price' => 80.00,
        ]);

        $margin = $product->getMargin();

        $this->assertEquals(-20.0, $margin);
    }

    public function test_has_negative_margin_returns_true_when_sale_below_purchase(): void
    {
        $product = Product::factory()->negativeMargin()->create();

        $this->assertTrue($product->hasNegativeMargin());
    }

    public function test_has_negative_margin_returns_false_when_sale_above_purchase(): void
    {
        $product = Product::factory()->create([
            'purchase_price' => 100.00,
            'sale_price' => 150.00,
        ]);

        $this->assertFalse($product->hasNegativeMargin());
    }

    // Stock Level Detection tests (Property 8)

    public function test_is_low_stock_returns_true_when_at_minimum(): void
    {
        $product = Product::factory()->create([
            'min_stock' => 10,
            'current_stock' => 10,
        ]);

        $this->assertTrue($product->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_below_minimum(): void
    {
        $product = Product::factory()->create([
            'min_stock' => 10,
            'current_stock' => 5,
        ]);

        $this->assertTrue($product->isLowStock());
    }

    public function test_is_low_stock_returns_false_when_above_minimum(): void
    {
        $product = Product::factory()->create([
            'min_stock' => 10,
            'current_stock' => 15,
        ]);

        $this->assertFalse($product->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_zero_stock(): void
    {
        $product = Product::factory()->create([
            'min_stock' => 5,
            'current_stock' => 0,
        ]);

        $this->assertTrue($product->isLowStock());
    }

    // Deletion protection tests

    public function test_can_delete_returns_true_when_no_active_children(): void
    {
        $product = Product::factory()->create();

        $this->assertTrue($product->canDelete());
    }

    public function test_can_delete_returns_false_when_has_active_children(): void
    {
        $product = Product::factory()->create();
        ProductChild::factory()->create(['product_id' => $product->id, 'is_active' => true]);

        $this->assertFalse($product->canDelete());
    }

    public function test_can_delete_returns_true_when_only_inactive_children(): void
    {
        $product = Product::factory()->create();
        ProductChild::factory()->create(['product_id' => $product->id, 'is_active' => false]);

        $this->assertTrue($product->canDelete());
    }

    // Scope tests

    public function test_active_scope_filters_active_products(): void
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);

        $activeProducts = Product::active()->get();

        $this->assertCount(1, $activeProducts);
        $this->assertTrue($activeProducts->first()->is_active);
    }

    // Image tests (Property 12)

    public function test_get_display_image_returns_image_when_set(): void
    {
        $product = Product::factory()->withImage()->create();

        $this->assertNotNull($product->getDisplayImage());
        $this->assertEquals($product->image, $product->getDisplayImage());
    }

    public function test_get_display_image_returns_null_when_no_image(): void
    {
        $product = Product::factory()->create(['image' => null]);

        $this->assertNull($product->getDisplayImage());
    }

    // Attribute accessors tests

    public function test_active_children_count_attribute(): void
    {
        $product = Product::factory()->create();
        ProductChild::factory()->count(3)->create(['product_id' => $product->id, 'is_active' => true]);
        ProductChild::factory()->count(2)->create(['product_id' => $product->id, 'is_active' => false]);

        $this->assertEquals(3, $product->active_children_count);
    }

    public function test_children_count_attribute(): void
    {
        $product = Product::factory()->create();
        ProductChild::factory()->count(5)->create(['product_id' => $product->id]);

        $this->assertEquals(5, $product->children_count);
    }
}
