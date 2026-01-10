<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductsModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create products module with permissions
        $module = Module::firstOrCreate(
            ['name' => 'products'],
            [
                'display_name' => 'Productos',
                'icon' => 'cube',
                'order' => 23,
            ]
        );

        $permissions = [
            ['name' => 'products.view', 'display_name' => 'Ver Productos'],
            ['name' => 'products.create', 'display_name' => 'Crear Productos'],
            ['name' => 'products.edit', 'display_name' => 'Editar Productos'],
            ['name' => 'products.delete', 'display_name' => 'Eliminar Productos'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'display_name' => $permissionData['display_name'],
                    'module_id' => $module->id,
                ]
            );
        }

        // Create product_field_config module with permissions
        $fieldConfigModule = Module::firstOrCreate(
            ['name' => 'product_field_config'],
            [
                'display_name' => 'Configuración de Campos de Producto',
                'icon' => 'adjustments-horizontal',
                'order' => 24,
            ]
        );

        $fieldConfigPermissions = [
            ['name' => 'product_field_config.view', 'display_name' => 'Ver Configuración de Campos'],
            ['name' => 'product_field_config.edit', 'display_name' => 'Editar Configuración de Campos'],
        ];

        foreach ($fieldConfigPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'display_name' => $permissionData['display_name'],
                    'module_id' => $fieldConfigModule->id,
                ]
            );
        }

        // Assign permissions to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $productPermissions = Permission::where('name', 'like', 'products.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($productPermissions);
            
            $fieldConfigPerms = Permission::where('name', 'like', 'product_field_config.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($fieldConfigPerms);
        }

        // Assign permissions to branch_admin role
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $productPermissions = Permission::where('name', 'like', 'products.%')->pluck('id');
            $branchAdmin->permissions()->syncWithoutDetaching($productPermissions);
        }
    }
}
