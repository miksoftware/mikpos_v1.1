<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_fillable_attributes(): void
    {
        $fillable = ['name', 'description', 'image', 'is_active'];
        
        $category = new Category();
        
        $this->assertEquals($fillable, $category->getFillable());
    }

    public function test_category_casts_is_active_to_boolean(): void
    {
        $category = Category::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    public function test_category_has_many_subcategories(): void
    {
        $category = Category::factory()->create();
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $category->subcategories);
        $this->assertTrue($category->subcategories->contains($subcategory));
    }

    public function test_category_can_be_created_with_factory(): void
    {
        $category = Category::factory()->create();
        
        $this->assertInstanceOf(Category::class, $category);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    public function test_inactive_category_factory_state(): void
    {
        $category = Category::factory()->inactive()->create();
        
        $this->assertFalse($category->is_active);
    }
}