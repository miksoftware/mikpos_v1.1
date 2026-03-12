<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemDocument;
use Illuminate\Database\Seeder;

class EcommerceModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'ecommerce'],
            [
                'display_name' => 'E-commerce',
                'icon' => 'shopping-bag',
                'is_active' => true,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'ecommerce_orders.view', 'display_name' => 'Ver pedidos e-commerce', 'description' => 'Ver listado de pedidos e-commerce'],
            ['name' => 'ecommerce_orders.approve', 'display_name' => 'Aprobar pedidos e-commerce', 'description' => 'Aprobar pedidos de la tienda en línea'],
            ['name' => 'ecommerce_orders.reject', 'display_name' => 'Rechazar pedidos e-commerce', 'description' => 'Rechazar pedidos de la tienda en línea'],
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

        // Create SystemDocument for e-commerce sales
        SystemDocument::firstOrCreate(
            ['code' => 'ecommerce-sale'],
            [
                'name' => 'Venta E-commerce',
                'prefix' => 'ECM',
                'description' => 'Documento para ventas generadas desde la tienda en línea',
                'next_number' => 1,
                'is_active' => true,
            ]
        );
    }
}
