<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Subcategories;
use App\Models\Category;
use App\Models\Role;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubcategoriesTest extends TestCase
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

    public function test_subcategories_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/subcategories');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Subcategories::class);
    }

    public function test_subcategories_page_displays_subcategories_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $subcategory = Subcategory::factory()->create(['name' => 'Test Subcategory']);
        
        Livewire::test(Subcategories::class)
            ->assertSee('Test Subcategory');
    }

    public function test_subcategories_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $subcategory1 = Subcategory::factory()->create(['name' => 'Smartphones']);
        $subcategory2 = Subcategory::factory()->create(['name' => 'Laptops']);
        
        Livewire::test(Subcategories::class)
            ->set('search', 'Smartphones')
            ->assertSee('Smartphones')
            ->assertDontSee('Laptops');
    }

    public function test_subcategories_can_be_filtered_by_category(): void
    {
        $this->actingAs($this->adminUser);
        
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        
        $subcategory1 = Subcategory::factory()->create(['category_id' => $category1->id, 'name' => 'Subcategory 1']);
        $subcategory2 = Subcategory::factory()->create(['category_id' => $category2->id, 'name' => 'Subcategory 2']);
        
        Livewire::test(Subcategories::class)
            ->set('filterCategory', $category1->id)
            ->assertSee('Subcategory 1')
            ->assertDontSee('Subcategory 2');
    }

    public function test_user_with_permission_can_create_subcategory(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create();
        
        Livewire::test(Subcategories::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('category_id', $category->id)
            ->set('name', 'New Subcategory')
            ->set('description', 'Subcategory description')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('subcategories', [
            'category_id' => $category->id,
            'name' => 'New Subcategory',
            'description' => 'Subcategory description',
        ]);
    }

    public function test_user_without_permission_cannot_create_subcategory(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Subcategories::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_subcategory(): void
    {
        $this->actingAs($this->adminUser);
        
        $subcategory = Subcategory::factory()->create([
            'name' => 'Original Subcategory',
        ]);
        
        Livewire::test(Subcategories::class)
            ->call('edit', $subcategory->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $subcategory->id)
            ->set('name', 'Updated Subcategory')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('subcategories', [
            'id' => $subcategory->id,
            'name' => 'Updated Subcategory',
        ]);
    }

    public function test_user_with_permission_can_delete_subcategory(): void
    {
        $this->actingAs($this->adminUser);
        
        $subcategory = Subcategory::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Subcategories::class)
            ->call('confirmDelete', $subcategory->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('subcategories', [
            'id' => $subcategory->id,
        ]);
    }

    public function test_subcategory_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $subcategory = Subcategory::factory()->create(['is_active' => true]);
        
        Livewire::test(Subcategories::class)
            ->call('toggleStatus', $subcategory->id);
        
        $this->assertDatabaseHas('subcategories', [
            'id' => $subcategory->id,
            'is_active' => false,
        ]);
    }

    public function test_subcategory_category_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Subcategories::class)
            ->call('create')
            ->set('category_id', '')
            ->set('name', 'Test Subcategory')
            ->call('store')
            ->assertHasErrors(['category_id']);
    }

    public function test_subcategory_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create();
        
        Livewire::test(Subcategories::class)
            ->call('create')
            ->set('category_id', $category->id)
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_categories_are_available_in_form(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create(['name' => 'Electronics']);
        
        Livewire::test(Subcategories::class)
            ->assertSee('Electronics');
    }

    public function test_activity_log_is_created_on_subcategory_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        $category = Category::factory()->create();
        
        Livewire::test(Subcategories::class)
            ->call('create')
            ->set('category_id', $category->id)
            ->set('name', 'Logged Subcategory')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'subcategories',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_subcategory_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $subcategory = Subcategory::factory()->create(['name' => 'Original']);
        
        Livewire::test(Subcategories::class)
            ->call('edit', $subcategory->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'subcategories',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_subcategory_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $subcategory = Subcategory::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Subcategories::class)
            ->call('confirmDelete', $subcategory->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'subcategories',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}