<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class ServicesModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'services'],
            [
                'display_name' => 'Servicios',
                'icon' => 'briefcase',
                'order' => 35,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'services.view', 'display_name' => 'Ver servicios', 'module_id' => $module->id],
            ['name' => 'services.create', 'display_name' => 'Crear servicios', 'module_id' => $module->id],
            ['name' => 'services.edit', 'display_name' => 'Editar servicios', 'module_id' => $module->id],
            ['name' => 'services.delete', 'display_name' => 'Eliminar servicios', 'module_id' => $module->id],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign to super_admin and branch_admin roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();

        $permissionIds = Permission::where('name', 'like', 'services.%')->pluck('id');

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
