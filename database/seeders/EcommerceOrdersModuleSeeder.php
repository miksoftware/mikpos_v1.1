<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class EcommerceOrdersModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'ecommerce_orders'],
            [
                'display_name' => 'Pedidos Tienda',
                'icon' => 'shopping-bag',
                'order' => 25,
                'is_active' => true,
            ]
        );

        $permissions = [
            ['name' => 'ecommerce_orders.view', 'display_name' => 'Ver pedidos tienda', 'description' => 'Ver listado de pedidos de la tienda en línea'],
            ['name' => 'ecommerce_orders.approve', 'display_name' => 'Aprobar pedidos', 'description' => 'Aprobar pedidos de la tienda en línea'],
            ['name' => 'ecommerce_orders.reject', 'display_name' => 'Rechazar pedidos', 'description' => 'Rechazar pedidos de la tienda en línea'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                [
                    'display_name' => $perm['display_name'],
                    'description' => $perm['description'],
                    'module_id' => $module->id,
                ]
            );
        }

        // Assign to super_admin and branch_admin roles
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        $permissionIds = Permission::where('name', 'like', 'ecommerce_orders.%')->pluck('id');

        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
