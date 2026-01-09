<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Municipalities;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MunicipalitiesTest extends TestCase
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

    public function test_municipalities_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/municipalities');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Municipalities::class);
    }

    public function test_municipalities_page_displays_municipalities_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $municipality = Municipality::factory()->create(['name' => 'Test Municipality']);
        
        Livewire::test(Municipalities::class)
            ->assertSee('Test Municipality');
    }

    public function test_municipalities_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $municipality1 = Municipality::factory()->create(['name' => 'Medellín']);
        $municipality2 = Municipality::factory()->create(['name' => 'Bogotá']);
        
        Livewire::test(Municipalities::class)
            ->set('search', 'Medellín')
            ->assertSee('Medellín')
            ->assertDontSee('Bogotá');
    }

    public function test_municipalities_can_be_filtered_by_department(): void
    {
        $this->actingAs($this->adminUser);
        
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        
        $municipality1 = Municipality::factory()->create([
            'department_id' => $department1->id,
            'name' => 'Municipality 1'
        ]);
        $municipality2 = Municipality::factory()->create([
            'department_id' => $department2->id,
            'name' => 'Municipality 2'
        ]);
        
        Livewire::test(Municipalities::class)
            ->set('filterDepartment', $department1->id)
            ->assertSee('Municipality 1')
            ->assertDontSee('Municipality 2');
    }

    public function test_user_with_permission_can_create_municipality(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        
        Livewire::test(Municipalities::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('department_id', $department->id)
            ->set('name', 'New Municipality')
            ->set('dian_code', '0001')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('municipalities', [
            'department_id' => $department->id,
            'name' => 'New Municipality',
            'dian_code' => '0001',
        ]);
    }

    public function test_user_without_permission_cannot_create_municipality(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Municipalities::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_municipality(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create([
            'department_id' => $department->id,
            'name' => 'Original Municipality',
        ]);
        
        Livewire::test(Municipalities::class)
            ->call('edit', $municipality->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('municipalityId', $municipality->id)
            ->set('name', 'Updated Municipality')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('municipalities', [
            'id' => $municipality->id,
            'name' => 'Updated Municipality',
        ]);
    }

    public function test_user_with_permission_can_delete_municipality(): void
    {
        $this->actingAs($this->adminUser);
        
        $municipality = Municipality::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Municipalities::class)
            ->call('confirmDelete', $municipality->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('municipalities', ['id' => $municipality->id]);
    }

    public function test_municipality_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $municipality = Municipality::factory()->create(['is_active' => true]);
        
        Livewire::test(Municipalities::class)
            ->call('toggleStatus', $municipality->id);
        
        $this->assertDatabaseHas('municipalities', [
            'id' => $municipality->id,
            'is_active' => false,
        ]);
    }

    public function test_municipality_department_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Municipalities::class)
            ->call('create')
            ->set('department_id', '')
            ->set('name', 'Test Municipality')
            ->call('store')
            ->assertHasErrors(['department_id']);
    }

    public function test_municipality_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        
        Livewire::test(Municipalities::class)
            ->call('create')
            ->set('department_id', $department->id)
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_departments_are_available_in_form(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create(['name' => 'Test Department']);
        
        Livewire::test(Municipalities::class)
            ->assertViewHas('departments', function ($departments) use ($department) {
                return $departments->contains('id', $department->id);
            });
    }

    public function test_activity_log_is_created_on_municipality_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        
        Livewire::test(Municipalities::class)
            ->call('create')
            ->set('department_id', $department->id)
            ->set('name', 'Logged Municipality')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'municipalities',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_municipality_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $municipality = Municipality::factory()->create(['name' => 'Original']);
        
        Livewire::test(Municipalities::class)
            ->call('edit', $municipality->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'municipalities',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_municipality_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $municipality = Municipality::factory()->create();
        
        Livewire::test(Municipalities::class)
            ->call('confirmDelete', $municipality->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'municipalities',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}