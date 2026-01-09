<?php

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_has_fillable_attributes(): void
    {
        $permission = new Permission();
        
        $this->assertEquals([
            'module_id',
            'name',
            'display_name',
            'description',
        ], $permission->getFillable());
    }

    public function test_permission_belongs_to_module(): void
    {
        $module = Module::factory()->create();
        $permission = Permission::factory()->create(['module_id' => $module->id]);
        
        $this->assertInstanceOf(Module::class, $permission->module);
        $this->assertEquals($module->id, $permission->module->id);
    }

    public function test_permission_belongs_to_many_roles(): void
    {
        $module = Module::factory()->create();
        $permission = Permission::factory()->create(['module_id' => $module->id]);
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        
        $permission->roles()->attach([$role1->id, $role2->id]);
        
        $this->assertCount(2, $permission->roles);
    }

    public function test_permission_can_be_created_with_factory(): void
    {
        $module = Module::factory()->create();
        $permission = Permission::factory()->create([
            'module_id' => $module->id,
            'name' => 'users.view',
            'display_name' => 'Ver Usuarios',
        ]);
        
        $this->assertDatabaseHas('permissions', [
            'name' => 'users.view',
            'display_name' => 'Ver Usuarios',
        ]);
    }
}
