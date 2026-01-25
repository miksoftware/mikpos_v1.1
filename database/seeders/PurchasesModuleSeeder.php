<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PurchasesModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'purchases'],
            [
                'display_name' => 'Compras',
                'is_active' => true,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'purchases.view', 'display_name' => 'Ver compras', 'description' => 'Ver listado de compras'],
            ['name' => 'purchases.create', 'display_name' => 'Crear compras', 'description' => 'Crear nuevas compras'],
            ['name' => 'purchases.edit', 'display_name' => 'Editar compras', 'description' => 'Editar y completar compras'],
            ['name' => 'purchases.delete', 'display_name' => 'Eliminar compras', 'description' => 'Eliminar compras en borrador'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'display_name' => $permData['display_name'],
                    'description' => $permData['description'],
                    'module_id' => $module->id,
                ]
            );
        }

        // Assign permissions to super_admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $permissionIds = Permission::where('name', 'like', 'purchases.%')->pluck('id');
            $superAdminRole->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign view and create to branch_admin
        $branchAdminRole = Role::where('name', 'branch_admin')->first();
        if ($branchAdminRole) {
            $permissionIds = Permission::whereIn('name', ['purchases.view', 'purchases.create', 'purchases.edit'])->pluck('id');
            $branchAdminRole->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
