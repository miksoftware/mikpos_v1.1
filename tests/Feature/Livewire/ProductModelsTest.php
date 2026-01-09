<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProductModels;
use App\Models\Brand;
use App\Models\ProductModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed();
        
        $user = User::where('email', 'admin@mikpos.com')->first();
        $this->actingAs($user);
    }

    public function test_component_can_render(): void
    {
        $component = Livewire::test(ProductModels::class);
        
        $component->assertStatus(200);
    }

    public function test_can_create_product_model(): void
    {
        $brand = Brand::factory()->create(['name' => 'Samsung']);
        
        Livewire::test(ProductModels::class)
            ->call('create')
            ->set('name', 'Galaxy S24')
            ->set('description', 'Latest Samsung smartphone')
            ->set('brand_id', $brand->id)
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_models', [
            'name' => 'Galaxy S24',
            'description' => 'Latest Samsung smartphone',
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
    }

    public function test_can_create_product_model_without_brand(): void
    {
        Livewire::test(ProductModels::class)
            ->call('create')
            ->set('name', 'Generic Model')
            ->set('description', 'Generic product model')
            ->set('brand_id', null)
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_models', [
            'name' => 'Generic Model',
            'description' => 'Generic product model',
            'brand_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_can_edit_product_model(): void
    {
        $brand = Brand::factory()->create(['name' => 'Apple']);
        $productModel = ProductModel::factory()->create([
            'name' => 'iPhone 14',
            'brand_id' => $brand->id,
        ]);

        Livewire::test(ProductModels::class)
            ->call('edit', $productModel->id)
            ->set('name', 'iPhone 15')
            ->set('description', 'Updated iPhone model')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_models', [
            'id' => $productModel->id,
            'name' => 'iPhone 15',
            'description' => 'Updated iPhone model',
        ]);
    }

    public function test_can_delete_product_model(): void
    {
        $productModel = ProductModel::factory()->create(['name' => 'Test Model']);

        Livewire::test(ProductModels::class)
            ->call('confirmDelete', $productModel->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_models', [
            'id' => $productModel->id,
        ]);
    }

    public function test_can_toggle_product_model_status(): void
    {
        $productModel = ProductModel::factory()->create(['is_active' => true]);

        Livewire::test(ProductModels::class)
            ->call('toggleStatus', $productModel->id);

        $this->assertDatabaseHas('product_models', [
            'id' => $productModel->id,
            'is_active' => false,
        ]);
    }

    public function test_can_search_product_models(): void
    {
        ProductModel::factory()->create(['name' => 'Galaxy S24']);
        ProductModel::factory()->create(['name' => 'iPhone 15']);

        $component = Livewire::test(ProductModels::class)
            ->set('search', 'Galaxy');

        $component->assertSee('Galaxy S24')
                  ->assertDontSee('iPhone 15');
    }

    public function test_can_filter_by_brand(): void
    {
        $samsung = Brand::factory()->create(['name' => 'Samsung']);
        $apple = Brand::factory()->create(['name' => 'Apple']);
        
        ProductModel::factory()->create(['name' => 'Galaxy S24', 'brand_id' => $samsung->id]);
        ProductModel::factory()->create(['name' => 'iPhone 15', 'brand_id' => $apple->id]);

        $component = Livewire::test(ProductModels::class)
            ->set('filterBrand', $samsung->id);

        $component->assertSee('Galaxy S24')
                  ->assertDontSee('iPhone 15');
    }

    public function test_name_is_required(): void
    {
        Livewire::test(ProductModels::class)
            ->call('create')
            ->set('name', '')
            ->set('description', 'Test description')
            ->call('store')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_name_must_be_at_least_2_characters(): void
    {
        Livewire::test(ProductModels::class)
            ->call('create')
            ->set('name', 'A')
            ->call('store')
            ->assertHasErrors(['name' => 'min']);
    }
}