<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'manufacturing'],
            [
                'display_name' => 'Fabricación',
                'icon' => 'wrench-screwdriver',
                'order' => 15,
                'is_active' => true,
            ]
        );

        $permissions = [
            ['name' => 'recipes.view', 'display_name' => 'Ver recetas'],
            ['name' => 'recipes.create', 'display_name' => 'Crear recetas'],
            ['name' => 'recipes.edit', 'display_name' => 'Editar recetas'],
            ['name' => 'recipes.delete', 'display_name' => 'Eliminar recetas'],
            ['name' => 'production.view', 'display_name' => 'Ver órdenes de producción'],
            ['name' => 'production.create', 'display_name' => 'Crear órdenes de producción'],
            ['name' => 'production.cancel', 'display_name' => 'Cancelar órdenes de producción'],
        ];

        $newPermissionIds = [];
        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'display_name' => $permissionData['display_name'],
                    'module_id' => $module->id,
                ]
            );
            $newPermissionIds[] = $permission->id;
        }

        // Assign to super_admin and branch_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($newPermissionIds);
        }
        
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($newPermissionIds);
        }

        // Also ensure SystemDocument exists for Production
        $doc = DB::table('system_documents')->where('code', 'PROD')->first();
        if (!$doc) {
            DB::table('system_documents')->insert([
                'code' => 'PROD',
                'name' => 'Orden de Producción',
                'prefix' => 'OP',
                'next_number' => 1,
                'description' => 'Documento para ingreso y salida de inventario por producción',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
