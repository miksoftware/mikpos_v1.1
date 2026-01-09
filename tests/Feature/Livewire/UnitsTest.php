<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Units;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitsTest extends TestCase
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

    public function test_units_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/units');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Units::class);
    }

    public function test_units_page_displays_units_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $unit = Unit::factory()->create(['name' => 'Test Unit']);
        
        Livewire::test(Units::class)
            ->assertSee('Test Unit');
    }

    public function test_units_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $unit1 = Unit::factory()->create(['name' => 'Kilogramo Especial', 'abbreviation' => 'KGE']);
        $unit2 = Unit::factory()->create(['name' => 'Litro Especial', 'abbreviation' => 'LTE']);
        
        Livewire::test(Units::class)
            ->set('search', 'Kilogramo')
            ->assertSee('Kilogramo Especial')
            ->assertDontSee('Litro Especial');
    }

    public function test_user_with_permission_can_create_unit(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Units::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'Kilogramo')
            ->set('abbreviation', 'kg')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('units', [
            'name' => 'Kilogramo',
            'abbreviation' => 'KG', // Should be uppercase
        ]);
    }

    public function test_user_without_permission_cannot_create_unit(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Units::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_unit(): void
    {
        $this->actingAs($this->adminUser);
        
        $unit = Unit::factory()->create([
            'name' => 'Original Unit',
        ]);
        
        Livewire::test(Units::class)
            ->call('edit', $unit->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $unit->id)
            ->set('name', 'Updated Unit')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Updated Unit',
        ]);
    }

    public function test_user_with_permission_can_delete_unit(): void
    {
        $this->actingAs($this->adminUser);
        
        $unit = Unit::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Units::class)
            ->call('confirmDelete', $unit->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('units', [
            'id' => $unit->id,
        ]);
    }

    public function test_unit_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $unit = Unit::factory()->create(['is_active' => true]);
        
        Livewire::test(Units::class)
            ->call('toggleStatus', $unit->id);
        
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'is_active' => false,
        ]);
    }

    public function test_unit_name_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Unit::factory()->create(['name' => 'Kilogramo']);
        
        Livewire::test(Units::class)
            ->call('create')
            ->set('name', 'Kilogramo')
            ->set('abbreviation', 'KG')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_unit_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Units::class)
            ->call('create')
            ->set('name', '')
            ->set('abbreviation', 'KG')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_unit_abbreviation_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Units::class)
            ->call('create')
            ->set('name', 'Kilogramo')
            ->set('abbreviation', '')
            ->call('store')
            ->assertHasErrors(['abbreviation']);
    }

    public function test_unit_abbreviation_is_converted_to_uppercase(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Units::class)
            ->call('create')
            ->set('name', 'Kilogramo')
            ->set('abbreviation', 'kg')
            ->call('store');
        
        $this->assertDatabaseHas('units', [
            'name' => 'Kilogramo',
            'abbreviation' => 'KG',
        ]);
    }

    public function test_activity_log_is_created_on_unit_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Units::class)
            ->call('create')
            ->set('name', 'Logged Unit')
            ->set('abbreviation', 'LU')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'units',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_unit_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $unit = Unit::factory()->create(['name' => 'Original']);
        
        Livewire::test(Units::class)
            ->call('edit', $unit->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'units',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_unit_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $unit = Unit::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Units::class)
            ->call('confirmDelete', $unit->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'units',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}