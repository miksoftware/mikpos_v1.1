<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Taxes;
use App\Models\Role;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxesTest extends TestCase
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

    public function test_taxes_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/taxes');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Taxes::class);
    }

    public function test_taxes_page_displays_taxes_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $tax = Tax::factory()->create(['name' => 'Test Tax']);
        
        Livewire::test(Taxes::class)
            ->assertSee('Test Tax');
    }

    public function test_taxes_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $tax1 = Tax::factory()->create(['name' => 'IVA 19%']);
        $tax2 = Tax::factory()->create(['name' => 'IVA 5%']);
        
        Livewire::test(Taxes::class)
            ->set('search', '19%')
            ->assertSee('IVA 19%')
            ->assertDontSee('IVA 5%');
    }

    public function test_user_with_permission_can_create_tax(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Taxes::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'IVA 19%')
            ->set('value', '19.00')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('taxes', [
            'name' => 'IVA 19%',
            'value' => '19.00',
        ]);
    }

    public function test_user_without_permission_cannot_create_tax(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Taxes::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_tax(): void
    {
        $this->actingAs($this->adminUser);
        
        $tax = Tax::factory()->create([
            'name' => 'Original Tax',
        ]);
        
        Livewire::test(Taxes::class)
            ->call('edit', $tax->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $tax->id)
            ->set('name', 'Updated Tax')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'name' => 'Updated Tax',
        ]);
    }

    public function test_user_with_permission_can_delete_tax(): void
    {
        $this->actingAs($this->adminUser);
        
        $tax = Tax::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Taxes::class)
            ->call('confirmDelete', $tax->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('taxes', [
            'id' => $tax->id,
        ]);
    }

    public function test_tax_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $tax = Tax::factory()->create(['is_active' => true]);
        
        Livewire::test(Taxes::class)
            ->call('toggleStatus', $tax->id);
        
        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'is_active' => false,
        ]);
    }

    public function test_tax_name_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Tax::factory()->create(['name' => 'IVA 19%']);
        
        Livewire::test(Taxes::class)
            ->call('create')
            ->set('name', 'IVA 19%')
            ->set('value', '19.00')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_tax_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Taxes::class)
            ->call('create')
            ->set('name', '')
            ->set('value', '19.00')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_tax_value_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Taxes::class)
            ->call('create')
            ->set('name', 'IVA 19%')
            ->set('value', '')
            ->call('store')
            ->assertHasErrors(['value']);
    }

    public function test_activity_log_is_created_on_tax_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Taxes::class)
            ->call('create')
            ->set('name', 'Logged Tax')
            ->set('value', '19.00')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'taxes',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_tax_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $tax = Tax::factory()->create(['name' => 'Original']);
        
        Livewire::test(Taxes::class)
            ->call('edit', $tax->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'taxes',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_tax_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $tax = Tax::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Taxes::class)
            ->call('confirmDelete', $tax->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'taxes',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}