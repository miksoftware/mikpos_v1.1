<?php

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_has_fillable_attributes(): void
    {
        $fillable = ['name', 'logo', 'is_active'];
        
        $brand = new Brand();
        
        $this->assertEquals($fillable, $brand->getFillable());
    }

    public function test_brand_casts_is_active_to_boolean(): void
    {
        $brand = Brand::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($brand->is_active);
        $this->assertTrue($brand->is_active);
    }

    public function test_brand_has_many_product_models(): void
    {
        $brand = Brand::factory()->create();
        $productModel = ProductModel::factory()->create(['brand_id' => $brand->id]);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $brand->productModels);
        $this->assertTrue($brand->productModels->contains($productModel));
    }

    public function test_brand_can_be_created_with_factory(): void
    {
        $brand = Brand::factory()->create();
        
        $this->assertInstanceOf(Brand::class, $brand);
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => $brand->name,
        ]);
    }

    public function test_inactive_brand_factory_state(): void
    {
        $brand = Brand::factory()->inactive()->create();
        
        $this->assertFalse($brand->is_active);
    }
}