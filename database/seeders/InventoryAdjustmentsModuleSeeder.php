<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class InventoryAdjustmentsModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'inventory_adjustments'],
            ['display_name' => 'Ajustes de Inventario', 'is_active' => true]
        );

        // Create permissions
        $permissions = [
            ['name' => 'inventory_adjustments.view', 'display_name' => 'Ver ajustes de inventario', 'description' => 'Ver listado de ajustes de inventario'],
            ['name' => 'inventory_adjustments.create', 'display_name' => 'Crear ajustes de inventario', 'description' => 'Crear nuevos ajustes de inventario'],
            ['name' => 'inventory_adjustments.delete', 'display_name' => 'Eliminar ajustes de inventario', 'description' => 'Eliminar ajustes de inventario'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name']],
                array_merge($permData, ['module_id' => $module->id])
            );
        }

        // Assign to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('name', 'like', 'inventory_adjustments.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
