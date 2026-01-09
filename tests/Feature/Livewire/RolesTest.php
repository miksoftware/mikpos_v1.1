<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Roles;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create super admin role with all permissions
        $this->superAdminRole = Role::factory()->superAdmin()->create();
        
        // Create admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->superAdminRole->id);
    }

    public function test_roles_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/roles');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Roles::class);
    }

    public function test_roles_page_displays_roles_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $role = Role::factory()->create(['display_name' => 'Test Role']);
        
        Livewire::test(Roles::class)
            ->assertSee('Test Role');
    }

    public function test_roles_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $role1 = Role::factory()->create(['display_name' => 'Administrador']);
        $role2 = Role::factory()->create(['display_name' => 'Cajero']);
        
        Livewire::test(Roles::class)
            ->set('search', 'Administrador')
            ->assertSee('Administrador')
            ->assertDontSee('Cajero');
    }

    public function test_user_with_permission_can_create_role(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Roles::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'new_role')
            ->set('display_name', 'New Role')
            ->set('description', 'A new test role')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('roles', [
            'name' => 'new_role',
            'display_name' => 'New Role',
        ]);
    }

    public function test_user_without_permission_cannot_create_role(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Roles::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_role(): void
    {
        $this->actingAs($this->adminUser);
        
        $role = Role::factory()->create([
            'name' => 'test_role',
            'display_name' => 'Test Role',
        ]);
        
        Livewire::test(Roles::class)
            ->call('edit', $role->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('roleId', $role->id)
            ->assertSet('name', 'test_role')
            ->set('display_name', 'Updated Role')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'display_name' => 'Updated Role',
        ]);
    }

    public function test_user_with_permission_can_delete_non_system_role(): void
    {
        $this->actingAs($this->adminUser);
        
        $role = Role::factory()->create([
            'name' => 'deletable_role',
            'is_system' => false,
        ]);
        
        Livewire::test(Roles::class)
            ->call('confirmDelete', $role->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $systemRole = Role::factory()->system()->create(['name' => 'system_role']);
        
        Livewire::test(Roles::class)
            ->call('confirmDelete', $systemRole->id)
            ->assertSet('isDeleteModalOpen', false)
            ->assertDispatched('notify');
        
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_role_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $role = Role::factory()->create(['is_active' => true, 'is_system' => false]);
        
        Livewire::test(Roles::class)
            ->call('toggleStatus', $role->id);
        
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'is_active' => false,
        ]);
    }

    public function test_super_admin_role_status_cannot_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Roles::class)
            ->call('toggleStatus', $this->superAdminRole->id)
            ->assertDispatched('notify');
        
        $this->assertDatabaseHas('roles', [
            'id' => $this->superAdminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_permissions_can_be_assigned_to_role(): void
    {
        $this->actingAs($this->adminUser);
        
        $module = Module::factory()->create();
        $permission = Permission::factory()->create([
            'module_id' => $module->id,
            'name' => 'test.view',
        ]);
        
        $role = Role::factory()->create();
        
        Livewire::test(Roles::class)
            ->call('edit', $role->id)
            ->call('togglePermission', $permission->id)
            ->assertSee($permission->id)
            ->call('store');
        
        $this->assertTrue($role->fresh()->hasPermission('test.view'));
    }

    public function test_role_name_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Role::factory()->create(['name' => 'existing_role']);
        
        Livewire::test(Roles::class)
            ->call('create')
            ->set('name', 'existing_role')
            ->set('display_name', 'Another Role')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_display_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Roles::class)
            ->call('create')
            ->set('name', 'new_role')
            ->set('display_name', '')
            ->call('store')
            ->assertHasErrors(['display_name']);
    }

    public function test_activity_log_is_created_on_role_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Roles::class)
            ->call('create')
            ->set('name', 'logged_role')
            ->set('display_name', 'Logged Role')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'roles',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_role_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $role = Role::factory()->create(['display_name' => 'Original']);
        
        Livewire::test(Roles::class)
            ->call('edit', $role->id)
            ->set('display_name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'roles',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_role_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $role = Role::factory()->create(['is_system' => false]);
        
        Livewire::test(Roles::class)
            ->call('confirmDelete', $role->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'roles',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}
