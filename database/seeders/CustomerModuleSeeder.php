<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create customers module
        $module = Module::create([
            'name' => 'customers',
            'display_name' => 'Clientes',
            'icon' => 'user-group',
            'order' => 22,
        ]);

        // Create permissions for customers module
        $permissions = [
            ['name' => 'customers.view', 'display_name' => 'Ver Clientes'],
            ['name' => 'customers.create', 'display_name' => 'Crear Clientes'],
            ['name' => 'customers.edit', 'display_name' => 'Editar Clientes'],
            ['name' => 'customers.delete', 'display_name' => 'Eliminar Clientes'],
        ];

        foreach ($permissions as $permissionData) {
            $module->permissions()->create($permissionData);
        }

        // Assign permissions to roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        $supervisor = Role::where('name', 'supervisor')->first();

        if ($superAdmin) {
            $superAdmin->permissions()->attach($module->permissions->pluck('id'));
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->attach($module->permissions->pluck('id'));
        }

        if ($supervisor) {
            // Supervisor gets view permission only
            $viewPermission = $module->permissions()->where('name', 'customers.view')->first();
            if ($viewPermission) {
                $supervisor->permissions()->attach($viewPermission->id);
            }
        }
    }
}