<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create customers module
        $module = Module::firstOrCreate(
            ['name' => 'customers'],
            ['display_name' => 'Clientes', 'icon' => 'user-group', 'order' => 22, 'is_active' => true]
        );

        // Create permissions for customers module
        $permissions = [
            ['name' => 'customers.view', 'display_name' => 'Ver Clientes'],
            ['name' => 'customers.create', 'display_name' => 'Crear Clientes'],
            ['name' => 'customers.edit', 'display_name' => 'Editar Clientes'],
            ['name' => 'customers.delete', 'display_name' => 'Eliminar Clientes'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                array_merge($permissionData, ['module_id' => $module->id])
            );
        }

        // Assign permissions to roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        $supervisor = Role::where('name', 'supervisor')->first();

        $permissionIds = Permission::where('name', 'like', 'customers.%')->pluck('id');

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($supervisor) {
            $viewPermission = Permission::where('name', 'customers.view')->first();
            if ($viewPermission) {
                $supervisor->permissions()->syncWithoutDetaching([$viewPermission->id]);
            }
        }
    }
}
