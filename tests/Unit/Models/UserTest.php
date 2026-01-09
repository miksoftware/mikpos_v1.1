<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_fillable_attributes(): void
    {
        $user = new User();
        
        $this->assertEquals([
            'name',
            'email',
            'password',
            'branch_id',
            'phone',
            'is_active',
            'avatar',
        ], $user->getFillable());
    }

    public function test_user_has_hidden_attributes(): void
    {
        $user = new User();
        
        $this->assertEquals([
            'password',
            'remember_token',
        ], $user->getHidden());
    }

    public function test_user_casts_is_active_to_boolean(): void
    {
        $user = User::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    public function test_user_belongs_to_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        
        $this->assertInstanceOf(Branch::class, $user->branch);
        $this->assertEquals($branch->id, $user->branch->id);
    }

    public function test_user_has_many_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        
        $user->roles()->attach($role->id);
        
        $this->assertCount(1, $user->roles);
        $this->assertEquals($role->id, $user->roles->first()->id);
    }

    public function test_user_is_super_admin(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        
        $user->roles()->attach($superAdminRole->id);
        
        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_user_is_not_super_admin(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'cashier']);
        
        $user->roles()->attach($role->id);
        
        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);
        
        $this->assertTrue($user->hasPermission('any.permission'));
        $this->assertTrue($user->hasPermission('users.create'));
        $this->assertTrue($user->hasPermission('roles.delete'));
    }

    public function test_user_has_permission_through_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'cashier']);
        $module = Module::factory()->create();
        $permission = Permission::factory()->create([
            'module_id' => $module->id,
            'name' => 'users.view'
        ]);
        
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);
        
        $this->assertTrue($user->hasPermission('users.view'));
    }

    public function test_user_does_not_have_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'cashier']);
        $user->roles()->attach($role->id);
        
        $this->assertFalse($user->hasPermission('users.delete'));
    }

    public function test_user_can_access_own_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        
        $this->assertTrue($user->canAccessBranch($branch->id));
    }

    public function test_user_cannot_access_other_branch(): void
    {
        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch1->id]);
        
        $this->assertFalse($user->canAccessBranch($branch2->id));
    }

    public function test_super_admin_can_access_any_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $user->roles()->attach($superAdminRole->id);
        
        $this->assertTrue($user->canAccessBranch($branch->id));
    }

    public function test_get_all_permissions_returns_array(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $module = Module::factory()->create();
        
        $permission1 = Permission::factory()->create(['module_id' => $module->id, 'name' => 'users.view']);
        $permission2 = Permission::factory()->create(['module_id' => $module->id, 'name' => 'users.create']);
        
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        $user->roles()->attach($role->id);
        
        $permissions = $user->getAllPermissions();
        
        $this->assertIsArray($permissions);
        $this->assertContains('users.view', $permissions);
        $this->assertContains('users.create', $permissions);
    }
}
