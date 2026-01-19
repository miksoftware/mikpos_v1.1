<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CombosModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create combos module
        $module = Module::firstOrCreate(
            ['name' => 'combos'],
            [
                'display_name' => 'Combos',
                'icon' => 'gift',
                'order' => 25,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'combos.view', 'display_name' => 'Ver Combos'],
            ['name' => 'combos.create', 'display_name' => 'Crear Combos'],
            ['name' => 'combos.edit', 'display_name' => 'Editar Combos'],
            ['name' => 'combos.delete', 'display_name' => 'Eliminar Combos'],
        ];

        $permissionIds = [];
        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'display_name' => $permissionData['display_name'],
                    'module_id' => $module->id,
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign permissions to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign permissions to branch_admin role
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
