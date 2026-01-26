<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SalesModuleSeeder extends Seeder
{
    /**
     * Seed the sales module permissions.
     */
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'sales'],
            [
                'display_name' => 'Ventas',
                'icon' => 'receipt',
                'is_active' => true,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'sales.view', 'display_name' => 'Ver ventas', 'description' => 'Ver listado de ventas'],
            ['name' => 'sales.retry_invoice', 'display_name' => 'Reintentar factura', 'description' => 'Reintentar envío de factura electrónica'],
        ];

        $permissionIds = [];
        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'module_id' => $module->id,
                    'display_name' => $permData['display_name'],
                    'description' => $permData['description'],
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Assign to super_admin and branch_admin roles
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin', 'supervisor'])->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
