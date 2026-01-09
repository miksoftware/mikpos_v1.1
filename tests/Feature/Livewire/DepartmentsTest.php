<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Departments;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentsTest extends TestCase
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

    public function test_departments_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/departments');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Departments::class);
    }

    public function test_departments_page_displays_departments_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create(['name' => 'Test Department']);
        
        Livewire::test(Departments::class)
            ->assertSee('Test Department');
    }

    public function test_departments_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $department1 = Department::factory()->create(['name' => 'Antioquia']);
        $department2 = Department::factory()->create(['name' => 'Cundinamarca']);
        
        Livewire::test(Departments::class)
            ->set('search', 'Antioquia')
            ->assertSee('Antioquia')
            ->assertDontSee('Cundinamarca');
    }

    public function test_user_with_permission_can_create_department(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Departments::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'New Department')
            ->set('dian_code', '01')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('departments', [
            'name' => 'New Department',
            'dian_code' => '01',
        ]);
    }

    public function test_user_without_permission_cannot_create_department(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Departments::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_department(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create([
            'name' => 'Original Department',
            'dian_code' => '01',
        ]);
        
        Livewire::test(Departments::class)
            ->call('edit', $department->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('departmentId', $department->id)
            ->set('name', 'Updated Department')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Updated Department',
        ]);
    }

    public function test_department_with_municipalities_cannot_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        Municipality::factory()->create(['department_id' => $department->id]);
        
        Livewire::test(Departments::class)
            ->call('confirmDelete', $department->id)
            ->call('delete')
            ->assertDispatched('notify');
        
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_department_without_municipalities_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Departments::class)
            ->call('confirmDelete', $department->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_department_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create(['is_active' => true]);
        
        Livewire::test(Departments::class)
            ->call('toggleStatus', $department->id);
        
        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => false,
        ]);
    }

    public function test_department_name_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Department::factory()->create(['name' => 'Existing Department']);
        
        Livewire::test(Departments::class)
            ->call('create')
            ->set('name', 'Existing Department')
            ->set('dian_code', '02')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_department_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Departments::class)
            ->call('create')
            ->set('name', '')
            ->set('dian_code', '01')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_dian_code_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Department::factory()->create(['dian_code' => '01']);
        
        Livewire::test(Departments::class)
            ->call('create')
            ->set('name', 'New Department')
            ->set('dian_code', '01')
            ->call('store')
            ->assertHasErrors(['dian_code']);
    }

    public function test_activity_log_is_created_on_department_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Departments::class)
            ->call('create')
            ->set('name', 'Logged Department')
            ->set('dian_code', '01')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'departments',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_department_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create(['name' => 'Original']);
        
        Livewire::test(Departments::class)
            ->call('edit', $department->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'departments',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_department_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        
        Livewire::test(Departments::class)
            ->call('confirmDelete', $department->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'departments',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}