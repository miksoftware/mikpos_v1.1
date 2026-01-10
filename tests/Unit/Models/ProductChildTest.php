<?php

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\ProductModel;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductChildTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_child_has_fillable_attributes(): void
    {
        $fillable = [
            'product_id', 'sku', 'barcode', 'name', 'presentation_id',
            'color_id', 'product_model_id', 'size', 'weight', 'purchase_price',
            'sale_price', 'price_includes_tax', 'min_stock', 'max_stock',
            'current_stock', 'image', 'imei', 'is_active',
        ];

        $child = new ProductChild();

        $this->assertEquals($fillable, $child->getFillable());
    }

    public function test_product_child_casts_is_active_to_boolean(): void
    {
        $child = ProductChild::factory()->create(['is_active' => 1]);

        $this->assertIsBool($child->is_active);
        $this->assertTrue($child->is_active);
    }

    public function test_product_child_casts_price_includes_tax_to_boolean(): void
    {
        $child = ProductChild::factory()->create(['price_includes_tax' => 1]);

        $this->assertIsBool($child->price_includes_tax);
        $this->assertTrue($child->price_includes_tax);
    }

    public function test_product_child_can_be_created_with_factory(): void
    {
        $child = ProductChild::factory()->create();

        $this->assertInstanceOf(ProductChild::class, $child);
        $this->assertDatabaseHas('product_children', [
            'id' => $child->id,
            'name' => $child->name,
        ]);
    }

    public function test_inactive_product_child_factory_state(): void
    {
        $child = ProductChild::factory()->inactive()->create();

        $this->assertFalse($child->is_active);
    }

    // Relationship tests

    public function test_product_child_belongs_to_product(): void
    {
        $product = Product::factory()->create();
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $child->product);
        $this->assertEquals($product->id, $child->product->id);
    }

    public function test_product_child_belongs_to_presentation(): void
    {
        $presentation = Presentation::factory()->create();
        $child = ProductChild::factory()->create(['presentation_id' => $presentation->id]);

        $this->assertInstanceOf(Presentation::class, $child->presentation);
        $this->assertEquals($presentation->id, $child->presentation->id);
    }

    public function test_product_child_belongs_to_color(): void
    {
        $color = Color::factory()->create();
        $child = ProductChild::factory()->create(['color_id' => $color->id]);

        $this->assertInstanceOf(Color::class, $child->color);
        $this->assertEquals($color->id, $child->color->id);
    }

    public function test_product_child_belongs_to_product_model(): void
    {
        $productModel = ProductModel::factory()->create();
        $child = ProductChild::factory()->create(['product_model_id' => $productModel->id]);

        $this->assertInstanceOf(ProductModel::class, $child->productModel);
        $this->assertEquals($productModel->id, $child->productModel->id);
    }

    // Price Margin Calculation tests (Property 7)

    public function test_get_margin_calculates_correct_percentage(): void
    {
        $child = ProductChild::factory()->create([
            'purchase_price' => 100.00,
            'sale_price' => 150.00,
        ]);

        $margin = $child->getMargin();

        $this->assertEquals(50.0, $margin);
    }

    public function test_get_margin_returns_null_for_zero_purchase_price(): void
    {
        $child = ProductChild::factory()->zeroPurchasePrice()->create();

        $margin = $child->getMargin();

        $this->assertNull($margin);
    }

    public function test_get_margin_returns_negative_for_loss(): void
    {
        $child = ProductChild::factory()->create([
            'purchase_price' => 100.00,
            'sale_price' => 80.00,
        ]);

        $margin = $child->getMargin();

        $this->assertEquals(-20.0, $margin);
    }

    public function test_get_margin_rounds_to_two_decimals(): void
    {
        $child = ProductChild::factory()->create([
            'purchase_price' => 33.33,
            'sale_price' => 50.00,
        ]);

        $margin = $child->getMargin();

        // (50 - 33.33) / 33.33 * 100 = 50.015...
        $this->assertEquals(50.02, $margin);
    }

    public function test_has_negative_margin_returns_true_when_sale_below_purchase(): void
    {
        $child = ProductChild::factory()->negativeMargin()->create();

        $this->assertTrue($child->hasNegativeMargin());
    }

    public function test_has_negative_margin_returns_false_when_sale_above_purchase(): void
    {
        $child = ProductChild::factory()->create([
            'purchase_price' => 100.00,
            'sale_price' => 150.00,
        ]);

        $this->assertFalse($child->hasNegativeMargin());
    }

    // Stock Level Detection tests (Property 8)

    public function test_is_low_stock_returns_true_when_at_minimum(): void
    {
        $child = ProductChild::factory()->create([
            'min_stock' => 10,
            'current_stock' => 10,
        ]);

        $this->assertTrue($child->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_below_minimum(): void
    {
        $child = ProductChild::factory()->create([
            'min_stock' => 10,
            'current_stock' => 5,
        ]);

        $this->assertTrue($child->isLowStock());
    }

    public function test_is_low_stock_returns_false_when_above_minimum(): void
    {
        $child = ProductChild::factory()->create([
            'min_stock' => 10,
            'current_stock' => 15,
        ]);

        $this->assertFalse($child->isLowStock());
    }

    public function test_is_low_stock_returns_true_when_zero_stock(): void
    {
        $child = ProductChild::factory()->create([
            'min_stock' => 5,
            'current_stock' => 0,
        ]);

        $this->assertTrue($child->isLowStock());
    }

    // Image Fallback Resolution tests (Property 12)

    public function test_get_display_image_returns_child_image_when_set(): void
    {
        $product = Product::factory()->withImage()->create();
        $child = ProductChild::factory()->withImage()->create(['product_id' => $product->id]);

        $displayImage = $child->getDisplayImage();

        $this->assertEquals($child->image, $displayImage);
        $this->assertNotEquals($product->image, $displayImage);
    }

    public function test_get_display_image_returns_parent_image_when_child_has_none(): void
    {
        $product = Product::factory()->withImage()->create();
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'image' => null,
        ]);

        $displayImage = $child->getDisplayImage();

        $this->assertEquals($product->image, $displayImage);
    }

    public function test_get_display_image_returns_null_when_neither_has_image(): void
    {
        $product = Product::factory()->create(['image' => null]);
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'image' => null,
        ]);

        $displayImage = $child->getDisplayImage();

        $this->assertNull($displayImage);
    }

    // Inherited fields tests (Property 4)

    public function test_category_id_accessor_returns_parent_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($category->id, $child->category_id);
    }

    public function test_subcategory_id_accessor_returns_parent_subcategory(): void
    {
        $category = Category::factory()->create();
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
        ]);
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($subcategory->id, $child->subcategory_id);
    }

    public function test_brand_id_accessor_returns_parent_brand(): void
    {
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($brand->id, $child->brand_id);
    }

    public function test_tax_id_accessor_returns_parent_tax(): void
    {
        $tax = Tax::factory()->create();
        $product = Product::factory()->create(['tax_id' => $tax->id]);
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($tax->id, $child->tax_id);
    }

    public function test_get_category_returns_parent_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Category::class, $child->getCategory());
        $this->assertEquals($category->id, $child->getCategory()->id);
    }

    public function test_get_brand_returns_parent_brand(): void
    {
        $brand = Brand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Brand::class, $child->getBrand());
        $this->assertEquals($brand->id, $child->getBrand()->id);
    }

    public function test_get_unit_returns_parent_unit(): void
    {
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);
        $child = ProductChild::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Unit::class, $child->getUnit());
        $this->assertEquals($unit->id, $child->getUnit()->id);
    }

    // Scope tests

    public function test_active_scope_filters_active_children(): void
    {
        $product = Product::factory()->create();
        ProductChild::factory()->create(['product_id' => $product->id, 'is_active' => true]);
        ProductChild::factory()->create(['product_id' => $product->id, 'is_active' => false]);

        $activeChildren = ProductChild::active()->get();

        $this->assertCount(1, $activeChildren);
        $this->assertTrue($activeChildren->first()->is_active);
    }

    public function test_low_stock_scope_filters_low_stock_children(): void
    {
        $product = Product::factory()->create();
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'min_stock' => 10,
            'current_stock' => 5,
        ]);
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'min_stock' => 10,
            'current_stock' => 20,
        ]);

        $lowStockChildren = ProductChild::lowStock()->get();

        $this->assertCount(1, $lowStockChildren);
    }

    // Full name attribute test

    public function test_full_name_attribute_combines_parent_and_child_names(): void
    {
        $product = Product::factory()->create(['name' => 'Parent Product']);
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'Child Variant',
        ]);

        $this->assertEquals('Parent Product - Child Variant', $child->full_name);
    }
}
