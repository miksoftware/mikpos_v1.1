<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class InventoryTransfersModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'inventory_transfers'],
            ['display_name' => 'Traslados de Inventario', 'is_active' => true]
        );

        $permissions = [
            ['name' => 'inventory_transfers.view', 'display_name' => 'Ver traslados', 'description' => 'Ver listado de traslados de inventario'],
            ['name' => 'inventory_transfers.create', 'display_name' => 'Crear traslados', 'description' => 'Crear nuevos traslados de inventario'],
            ['name' => 'inventory_transfers.delete', 'display_name' => 'Eliminar traslados', 'description' => 'Eliminar traslados de inventario'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                array_merge($perm, ['module_id' => $module->id])
            );
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('name', 'like', 'inventory_transfers.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
