<?php

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_has_fillable_attributes(): void
    {
        $role = new Role();
        
        $this->assertEquals([
            'name',
            'display_name',
            'description',
            'is_system',
            'is_active',
        ], $role->getFillable());
    }

    public function test_role_casts_booleans_correctly(): void
    {
        $role = Role::factory()->create([
            'is_system' => 1,
            'is_active' => 1,
        ]);
        
        $this->assertIsBool($role->is_system);
        $this->assertIsBool($role->is_active);
        $this->assertTrue($role->is_system);
        $this->assertTrue($role->is_active);
    }

    public function test_role_has_many_permissions(): void
    {
        $role = Role::factory()->create();
        $module = Module::factory()->create();
        $permission = Permission::factory()->create(['module_id' => $module->id]);
        
        $role->permissions()->attach($permission->id);
        
        $this->assertCount(1, $role->permissions);
        $this->assertEquals($permission->id, $role->permissions->first()->id);
    }

    public function test_role_has_many_users(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        
        $role->users()->attach($user->id);
        
        $this->assertCount(1, $role->users);
        $this->assertEquals($user->id, $role->users->first()->id);
    }

    public function test_role_has_permission(): void
    {
        $role = Role::factory()->create();
        $module = Module::factory()->create();
        $permission = Permission::factory()->create([
            'module_id' => $module->id,
            'name' => 'users.view'
        ]);
        
        $role->permissions()->attach($permission->id);
        
        $this->assertTrue($role->hasPermission('users.view'));
    }

    public function test_role_does_not_have_permission(): void
    {
        $role = Role::factory()->create();
        
        $this->assertFalse($role->hasPermission('users.delete'));
    }

    public function test_role_can_have_multiple_permissions(): void
    {
        $role = Role::factory()->create();
        $module = Module::factory()->create();
        
        $permission1 = Permission::factory()->create(['module_id' => $module->id, 'name' => 'users.view']);
        $permission2 = Permission::factory()->create(['module_id' => $module->id, 'name' => 'users.create']);
        $permission3 = Permission::factory()->create(['module_id' => $module->id, 'name' => 'users.edit']);
        
        $role->permissions()->attach([$permission1->id, $permission2->id, $permission3->id]);
        
        $this->assertCount(3, $role->permissions);
        $this->assertTrue($role->hasPermission('users.view'));
        $this->assertTrue($role->hasPermission('users.create'));
        $this->assertTrue($role->hasPermission('users.edit'));
    }

    public function test_system_role_factory_state(): void
    {
        $role = Role::factory()->system()->create();
        
        $this->assertTrue($role->is_system);
    }

    public function test_inactive_role_factory_state(): void
    {
        $role = Role::factory()->inactive()->create();
        
        $this->assertFalse($role->is_active);
    }

    public function test_super_admin_role_factory_state(): void
    {
        $role = Role::factory()->superAdmin()->create();
        
        $this->assertEquals('super_admin', $role->name);
        $this->assertTrue($role->is_system);
    }
}
