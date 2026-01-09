<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubcategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_subcategory_has_fillable_attributes(): void
    {
        $fillable = ['category_id', 'name', 'description', 'is_active'];
        
        $subcategory = new Subcategory();
        
        $this->assertEquals($fillable, $subcategory->getFillable());
    }

    public function test_subcategory_casts_is_active_to_boolean(): void
    {
        $subcategory = Subcategory::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($subcategory->is_active);
        $this->assertTrue($subcategory->is_active);
    }

    public function test_subcategory_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);
        
        $this->assertInstanceOf(Category::class, $subcategory->category);
        $this->assertEquals($category->id, $subcategory->category->id);
    }

    public function test_subcategory_can_be_created_with_factory(): void
    {
        $subcategory = Subcategory::factory()->create();
        
        $this->assertInstanceOf(Subcategory::class, $subcategory);
        $this->assertDatabaseHas('subcategories', [
            'id' => $subcategory->id,
            'name' => $subcategory->name,
        ]);
    }

    public function test_inactive_subcategory_factory_state(): void
    {
        $subcategory = Subcategory::factory()->inactive()->create();
        
        $this->assertFalse($subcategory->is_active);
    }
}