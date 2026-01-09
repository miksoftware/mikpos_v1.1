<?php

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_model_can_be_created(): void
    {
        $brand = Brand::factory()->create();
        
        $productModel = ProductModel::factory()->create([
            'brand_id' => $brand->id,
            'name' => 'Galaxy S24',
            'description' => 'Latest Samsung smartphone',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('product_models', [
            'brand_id' => $brand->id,
            'name' => 'Galaxy S24',
            'description' => 'Latest Samsung smartphone',
            'is_active' => true,
        ]);
    }

    public function test_product_model_belongs_to_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Samsung']);
        $productModel = ProductModel::factory()->create(['brand_id' => $brand->id]);

        $this->assertInstanceOf(Brand::class, $productModel->brand);
        $this->assertEquals('Samsung', $productModel->brand->name);
    }

    public function test_product_model_can_exist_without_brand(): void
    {
        $productModel = ProductModel::factory()->withoutBrand()->create([
            'name' => 'Generic Model',
        ]);

        $this->assertNull($productModel->brand_id);
        $this->assertNull($productModel->brand);
    }

    public function test_product_model_is_active_is_cast_to_boolean(): void
    {
        $productModel = ProductModel::factory()->create(['is_active' => 1]);
        $this->assertIsBool($productModel->is_active);
        $this->assertTrue($productModel->is_active);

        $productModel = ProductModel::factory()->inactive()->create();
        $this->assertIsBool($productModel->is_active);
        $this->assertFalse($productModel->is_active);
    }

    public function test_product_model_fillable_attributes(): void
    {
        $productModel = new ProductModel();
        $expected = ['brand_id', 'name', 'description', 'is_active'];
        
        $this->assertEquals($expected, $productModel->getFillable());
    }

    public function test_product_model_uses_correct_table(): void
    {
        $productModel = new ProductModel();
        $this->assertEquals('product_models', $productModel->getTable());
    }
}