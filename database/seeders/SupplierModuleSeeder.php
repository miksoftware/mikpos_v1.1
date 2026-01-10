<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SupplierModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create suppliers module
        $module = Module::firstOrCreate(
            ['name' => 'suppliers'],
            ['display_name' => 'Proveedores', 'is_active' => true]
        );

        // Create permissions
        $permissions = [
            ['name' => 'suppliers.view', 'display_name' => 'Ver proveedores', 'description' => 'Ver listado de proveedores'],
            ['name' => 'suppliers.create', 'display_name' => 'Crear proveedores', 'description' => 'Crear nuevos proveedores'],
            ['name' => 'suppliers.edit', 'display_name' => 'Editar proveedores', 'description' => 'Editar proveedores existentes'],
            ['name' => 'suppliers.delete', 'display_name' => 'Eliminar proveedores', 'description' => 'Eliminar proveedores'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['display_name' => $permission['display_name'], 'module_id' => $module->id]
            );
        }

        // Assign all permissions to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('name', 'like', 'suppliers.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign view, create, edit to branch_admin
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $permissionIds = Permission::whereIn('name', ['suppliers.view', 'suppliers.create', 'suppliers.edit'])->pluck('id');
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
