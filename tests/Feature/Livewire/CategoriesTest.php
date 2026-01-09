<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Categories;
use App\Models\Category;
use App\Models\Role;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesTest extends TestCase
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

    public function test_categories_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/categories');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Categories::class);
    }

    public function test_categories_page_displays_categories_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create(['name' => 'Test Category']);
        
        Livewire::test(Categories::class)
            ->assertSee('Test Category');
    }

    public function test_categories_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $category1 = Category::factory()->create(['name' => 'Electronics']);
        $category2 = Category::factory()->create(['name' => 'Clothing']);
        
        Livewire::test(Categories::class)
            ->set('search', 'Electronics')
            ->assertSee('Electronics')
            ->assertDontSee('Clothing');
    }

    public function test_user_with_permission_can_create_category(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Categories::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'New Category')
            ->set('description', 'Category description')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'description' => 'Category description',
        ]);
    }

    public function test_user_without_permission_cannot_create_category(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Categories::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_category(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create([
            'name' => 'Original Category',
        ]);
        
        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $category->id)
            ->set('name', 'Updated Category')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    public function test_category_with_subcategories_cannot_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create(['name' => 'Category with Subcategories']);
        Subcategory::factory()->create(['category_id' => $category->id]);
        
        Livewire::test(Categories::class)
            ->call('confirmDelete', $category->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false)
            ->assertDispatched('notify');
        
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_without_subcategories_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Categories::class)
            ->call('confirmDelete', $category->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create(['is_active' => true]);
        
        Livewire::test(Categories::class)
            ->call('toggleStatus', $category->id);
        
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Category::factory()->create(['name' => 'Electronics']);
        
        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Electronics')
            ->set('description', 'Another category')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_category_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', '')
            ->set('description', 'Category description')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_activity_log_is_created_on_category_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Logged Category')
            ->set('description', 'Category description')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'categories',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_category_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create(['name' => 'Original']);
        
        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'categories',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_category_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Categories::class)
            ->call('confirmDelete', $category->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'categories',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}