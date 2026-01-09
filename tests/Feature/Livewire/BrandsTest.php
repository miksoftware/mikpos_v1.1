<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Brands;
use App\Models\Brand;
use App\Models\ProductModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrandsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->superAdminRole->id);
    }

    public function test_brands_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/brands');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Brands::class);
    }

    public function test_brands_page_displays_brands_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create(['name' => 'Test Brand']);
        
        Livewire::test(Brands::class)
            ->assertSee('Test Brand');
    }

    public function test_brands_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand1 = Brand::factory()->create(['name' => 'Samsung']);
        $brand2 = Brand::factory()->create(['name' => 'Apple']);
        
        Livewire::test(Brands::class)
            ->set('search', 'Samsung')
            ->assertSee('Samsung')
            ->assertDontSee('Apple');
    }

    public function test_user_with_permission_can_create_brand(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Brands::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'New Brand')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('brands', [
            'name' => 'New Brand',
        ]);
    }

    public function test_user_without_permission_cannot_create_brand(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Brands::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_brand(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create([
            'name' => 'Original Brand',
        ]);
        
        Livewire::test(Brands::class)
            ->call('edit', $brand->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $brand->id)
            ->set('name', 'Updated Brand')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Brand',
        ]);
    }

    public function test_brand_with_product_models_cannot_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create(['name' => 'Brand with Models']);
        ProductModel::factory()->create(['brand_id' => $brand->id]);
        
        Livewire::test(Brands::class)
            ->call('confirmDelete', $brand->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false)
            ->assertDispatched('notify');
        
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_brand_without_product_models_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Brands::class)
            ->call('confirmDelete', $brand->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_brand_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create(['is_active' => true]);
        
        Livewire::test(Brands::class)
            ->call('toggleStatus', $brand->id);
        
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'is_active' => false,
        ]);
    }

    public function test_brand_name_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Brand::factory()->create(['name' => 'Samsung']);
        
        Livewire::test(Brands::class)
            ->call('create')
            ->set('name', 'Samsung')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_brand_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Brands::class)
            ->call('create')
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_brand_displays_product_model_count(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create(['name' => 'Samsung']);
        ProductModel::factory()->count(3)->create(['brand_id' => $brand->id]);
        
        Livewire::test(Brands::class)
            ->assertSee('Samsung')
            ->assertSee('3'); // Should show model count
    }

    public function test_activity_log_is_created_on_brand_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Brands::class)
            ->call('create')
            ->set('name', 'Logged Brand')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'brands',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_brand_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create(['name' => 'Original']);
        
        Livewire::test(Brands::class)
            ->call('edit', $brand->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'brands',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_brand_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Brands::class)
            ->call('confirmDelete', $brand->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'brands',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}