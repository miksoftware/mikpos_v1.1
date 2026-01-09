<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Branches;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchesTest extends TestCase
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

    public function test_branches_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/branches');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Branches::class);
    }

    public function test_branches_page_displays_branches_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['name' => 'Test Branch']);
        
        Livewire::test(Branches::class)
            ->assertSee('Test Branch');
    }

    public function test_branches_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch1 = Branch::factory()->create(['name' => 'Main Branch']);
        $branch2 = Branch::factory()->create(['name' => 'Secondary Branch']);
        
        Livewire::test(Branches::class)
            ->set('search', 'Main')
            ->assertSee('Main Branch')
            ->assertDontSee('Secondary Branch');
    }

    public function test_user_with_permission_can_create_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('code', 'SUC001')
            ->set('name', 'New Branch')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('branches', [
            'code' => 'SUC001',
            'name' => 'New Branch',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
        ]);
    }

    public function test_user_without_permission_cannot_create_branch(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $branch = Branch::factory()->create([
            'code' => 'SUC001',
            'name' => 'Original Branch',
        ]);
        
        Livewire::test(Branches::class)
            ->call('edit', $branch->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('branchId', $branch->id)
            ->set('name', 'Updated Branch')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Branch',
        ]);
    }

    public function test_user_with_permission_can_delete_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Branches::class)
            ->call('confirmDelete', $branch->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_active' => false,
        ]);
    }

    public function test_branch_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['is_active' => true]);
        
        Livewire::test(Branches::class)
            ->call('toggleStatus', $branch->id);
        
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_active' => false,
        ]);
    }

    public function test_branch_code_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Branch::factory()->create(['code' => 'SUC001']);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->set('code', 'SUC001')
            ->set('name', 'Another Branch')
            ->call('store')
            ->assertHasErrors(['code']);
    }

    public function test_branch_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->set('code', 'SUC001')
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_municipalities_load_when_department_changes(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        
        $component = Livewire::test(Branches::class)
            ->call('create')
            ->set('department_id', $department->id);
        
        $this->assertCount(1, $component->get('municipalities'));
        $this->assertEquals($municipality->id, $component->get('municipalities')[0]['id']);
    }

    public function test_activity_log_is_created_on_branch_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->set('code', 'SUC001')
            ->set('name', 'Logged Branch')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'branches',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_branch_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['name' => 'Original']);
        
        Livewire::test(Branches::class)
            ->call('edit', $branch->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'branches',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }
}