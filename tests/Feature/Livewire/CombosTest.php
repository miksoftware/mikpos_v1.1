<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Combos;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CombosTest extends TestCase
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

    // ==========================================
    // Page Rendering Tests
    // ==========================================

    public function test_combos_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/combos');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Combos::class);
    }

    public function test_combos_page_displays_combos_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $combo = Combo::factory()->create(['name' => 'Test Combo']);
        
        Livewire::test(Combos::class)
            ->assertSee('Test Combo');
    }

    // ==========================================
    // Create Tests
    // ==========================================

    public function test_user_with_permission_can_open_create_modal(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Combos::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', null);
    }

    public function test_user_without_permission_cannot_create_combo(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Combos::class)
            ->call('create')
            ->assertSet('isModalOpen', false);
    }

    public function test_combo_requires_at_least_two_products(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('create')
            ->set('name', 'Test Combo')
            ->set('combo_price', 50)
            ->set('comboItems', [
                ['type' => 'product', 'id' => $product->id, 'name' => $product->name, 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
            ])
            ->call('store')
            ->assertHasErrors(['comboItems']);
    }

    public function test_user_can_create_combo_with_valid_data(): void
    {
        $this->actingAs($this->adminUser);
        
        $product1 = Product::factory()->create(['sale_price' => 30]);
        $product2 = Product::factory()->create(['sale_price' => 40]);
        
        Livewire::test(Combos::class)
            ->call('create')
            ->set('name', 'New Combo')
            ->set('description', 'Test description')
            ->set('combo_price', 60)
            ->set('limit_type', 'none')
            ->set('comboItems', [
                ['type' => 'product', 'id' => $product1->id, 'name' => $product1->name, 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
                ['type' => 'product', 'id' => $product2->id, 'name' => $product2->name, 'price' => 40, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
            ])
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('combos', [
            'name' => 'New Combo',
            'combo_price' => 60,
        ]);
    }

    // ==========================================
    // Edit Tests
    // ==========================================

    public function test_user_with_permission_can_open_edit_modal(): void
    {
        $this->actingAs($this->adminUser);
        
        $combo = Combo::factory()->create(['name' => 'Existing Combo']);
        $product = Product::factory()->create();
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);
        
        Livewire::test(Combos::class)
            ->call('edit', $combo->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $combo->id)
            ->assertSet('name', 'Existing Combo');
    }

    public function test_user_without_permission_cannot_edit_combo(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        $combo = Combo::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('edit', $combo->id)
            ->assertSet('isModalOpen', false);
    }

    public function test_user_can_update_combo(): void
    {
        $this->actingAs($this->adminUser);
        
        $combo = Combo::factory()->create(['name' => 'Old Name']);
        $product1 = Product::factory()->create(['sale_price' => 30]);
        $product2 = Product::factory()->create(['sale_price' => 40]);
        
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'unit_price' => 30,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 40,
        ]);
        
        Livewire::test(Combos::class)
            ->call('edit', $combo->id)
            ->set('name', 'Updated Name')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('combos', [
            'id' => $combo->id,
            'name' => 'Updated Name',
        ]);
    }

    // ==========================================
    // Delete Tests
    // ==========================================

    public function test_user_with_permission_can_open_delete_modal(): void
    {
        $this->actingAs($this->adminUser);
        
        $combo = Combo::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('confirmDelete', $combo->id)
            ->assertSet('isDeleteModalOpen', true)
            ->assertSet('itemIdToDelete', $combo->id);
    }

    public function test_user_without_permission_cannot_delete_combo(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        $combo = Combo::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('confirmDelete', $combo->id)
            ->assertSet('isDeleteModalOpen', false);
    }

    public function test_user_can_delete_combo(): void
    {
        $this->actingAs($this->adminUser);
        
        $combo = Combo::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('confirmDelete', $combo->id)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('combos', ['id' => $combo->id]);
    }

    // ==========================================
    // Toggle Status Tests
    // ==========================================

    public function test_user_with_permission_can_toggle_status(): void
    {
        $this->actingAs($this->adminUser);
        
        $combo = Combo::factory()->create(['is_active' => true]);
        
        Livewire::test(Combos::class)
            ->call('toggleStatus', $combo->id);
        
        $this->assertFalse($combo->fresh()->is_active);
    }

    public function test_user_without_permission_cannot_toggle_status(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        $combo = Combo::factory()->create(['is_active' => true]);
        
        Livewire::test(Combos::class)
            ->call('toggleStatus', $combo->id);
        
        $this->assertTrue($combo->fresh()->is_active);
    }

    // ==========================================
    // Search and Filter Tests
    // ==========================================

    public function test_search_filters_combos_by_name(): void
    {
        $this->actingAs($this->adminUser);
        
        Combo::factory()->create(['name' => 'Combo Familiar']);
        Combo::factory()->create(['name' => 'Combo Ejecutivo']);
        
        Livewire::test(Combos::class)
            ->set('search', 'Familiar')
            ->assertSee('Combo Familiar')
            ->assertDontSee('Combo Ejecutivo');
    }

    public function test_filter_by_status_shows_active_combos(): void
    {
        $this->actingAs($this->adminUser);
        
        Combo::factory()->create(['name' => 'Active Combo', 'is_active' => true]);
        Combo::factory()->create(['name' => 'Inactive Combo', 'is_active' => false]);
        
        Livewire::test(Combos::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Combo')
            ->assertDontSee('Inactive Combo');
    }

    public function test_filter_by_limit_type(): void
    {
        $this->actingAs($this->adminUser);
        
        Combo::factory()->create(['name' => 'No Limit Combo', 'limit_type' => 'none']);
        Combo::factory()->withTimeLimit()->create(['name' => 'Time Limited Combo']);
        
        Livewire::test(Combos::class)
            ->set('filterLimitType', 'time')
            ->assertSee('Time Limited Combo')
            ->assertDontSee('No Limit Combo');
    }

    public function test_clear_filters_resets_all_filters(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Combos::class)
            ->set('search', 'test')
            ->set('filterStatus', '1')
            ->set('filterLimitType', 'time')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('filterStatus', null)
            ->assertSet('filterLimitType', null);
    }

    // ==========================================
    // Product Management Tests
    // ==========================================

    public function test_can_add_product_to_combo(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create(['name' => 'Test Product', 'sale_price' => 50]);
        
        Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product->id)
            ->assertCount('comboItems', 1);
    }

    public function test_cannot_add_duplicate_product(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product->id)
            ->call('addProduct', 'product', $product->id)
            ->assertCount('comboItems', 1)
            ->assertDispatched('notify');
    }

    public function test_can_remove_product_from_combo(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product->id)
            ->assertCount('comboItems', 1)
            ->call('removeProduct', 0)
            ->assertCount('comboItems', 0);
    }

    public function test_can_update_product_quantity(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create(['sale_price' => 50]);
        
        $component = Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product->id)
            ->call('updateQuantity', 0, 5);
        
        $comboItems = $component->get('comboItems');
        $this->assertEquals(5, $comboItems[0]['quantity']);
    }

    public function test_quantity_cannot_be_less_than_one(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create(['sale_price' => 50]);
        
        $component = Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product->id)
            ->call('updateQuantity', 0, 0);
        
        $comboItems = $component->get('comboItems');
        $this->assertEquals(1, $comboItems[0]['quantity']);
    }

    // ==========================================
    // Validation Tests
    // ==========================================

    public function test_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('create')
            ->set('name', '')
            ->set('combo_price', 50)
            ->set('comboItems', [
                ['type' => 'product', 'id' => $product1->id, 'name' => 'P1', 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
                ['type' => 'product', 'id' => $product2->id, 'name' => 'P2', 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
            ])
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_combo_price_cannot_be_negative(): void
    {
        $this->actingAs($this->adminUser);
        
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('create')
            ->set('name', 'Test Combo')
            ->set('combo_price', -10)
            ->set('comboItems', [
                ['type' => 'product', 'id' => $product1->id, 'name' => 'P1', 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
                ['type' => 'product', 'id' => $product2->id, 'name' => 'P2', 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
            ])
            ->call('store')
            ->assertHasErrors(['combo_price']);
    }

    public function test_max_sales_required_for_quantity_limit(): void
    {
        $this->actingAs($this->adminUser);
        
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        Livewire::test(Combos::class)
            ->call('create')
            ->set('name', 'Test Combo')
            ->set('combo_price', 50)
            ->set('limit_type', 'quantity')
            ->set('max_sales', null)
            ->set('comboItems', [
                ['type' => 'product', 'id' => $product1->id, 'name' => 'P1', 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
                ['type' => 'product', 'id' => $product2->id, 'name' => 'P2', 'price' => 30, 'quantity' => 1, 'image' => null, 'unit' => 'und'],
            ])
            ->call('store')
            ->assertHasErrors(['max_sales']);
    }

    // ==========================================
    // Computed Properties Tests
    // ==========================================

    public function test_original_price_computed_correctly(): void
    {
        $this->actingAs($this->adminUser);
        
        $product1 = Product::factory()->create(['sale_price' => 30]);
        $product2 = Product::factory()->create(['sale_price' => 40]);
        
        $component = Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product1->id)
            ->call('addProduct', 'product', $product2->id);
        
        $this->assertEquals(70, $component->get('original_price'));
    }

    public function test_savings_computed_correctly(): void
    {
        $this->actingAs($this->adminUser);
        
        $product1 = Product::factory()->create(['sale_price' => 30]);
        $product2 = Product::factory()->create(['sale_price' => 40]);
        
        $component = Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product1->id)
            ->call('addProduct', 'product', $product2->id)
            ->set('combo_price', 60);
        
        $this->assertEquals(10, $component->get('savings'));
    }

    public function test_savings_percentage_computed_correctly(): void
    {
        $this->actingAs($this->adminUser);
        
        $product1 = Product::factory()->create(['sale_price' => 50]);
        $product2 = Product::factory()->create(['sale_price' => 50]);
        
        $component = Livewire::test(Combos::class)
            ->call('create')
            ->call('addProduct', 'product', $product1->id)
            ->call('addProduct', 'product', $product2->id)
            ->set('combo_price', 80);
        
        // Original: 100, Combo: 80, Savings: 20, Percentage: 20%
        $this->assertEquals(20.0, $component->get('savings_percentage'));
    }
}
