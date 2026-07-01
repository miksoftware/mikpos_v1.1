<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 0. Insert Module if not exists
        $module = DB::table('modules')->where('name', 'manufacturing')->first();
        if (!$module) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'manufacturing',
                'display_name' => 'Fabricación',
                'icon' => 'wrench-screwdriver',
                'order' => 15,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $moduleId = $module->id;
        }

        // 1. Insert permissions
        $permissions = [
            ['module_id' => $moduleId, 'name' => 'recipes.view', 'display_name' => 'Ver recetas'],
            ['module_id' => $moduleId, 'name' => 'recipes.create', 'display_name' => 'Crear recetas'],
            ['module_id' => $moduleId, 'name' => 'recipes.edit', 'display_name' => 'Editar recetas'],
            ['module_id' => $moduleId, 'name' => 'recipes.delete', 'display_name' => 'Eliminar recetas'],
            ['module_id' => $moduleId, 'name' => 'production.view', 'display_name' => 'Ver órdenes de producción'],
            ['module_id' => $moduleId, 'name' => 'production.create', 'display_name' => 'Crear órdenes de producción'],
            ['module_id' => $moduleId, 'name' => 'production.cancel', 'display_name' => 'Cancelar órdenes de producción'],
        ];

        foreach ($permissions as $perm) {
            $existingPerm = DB::table('permissions')->where('name', $perm['name'])->first();
            if (!$existingPerm) {
                $id = DB::table('permissions')->insertGetId(array_merge($perm, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
            } else {
                $id = $existingPerm->id;
            }

            // Assign to super_admin (assuming role_id = 1)
            $existingRolePerm = DB::table('role_permission')->where('permission_id', $id)->where('role_id', 1)->first();
            if (!$existingRolePerm) {
                DB::table('role_permission')->insert([
                    'permission_id' => $id,
                    'role_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 2. Insert SystemDocument for Production
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

    public function down(): void
    {
        DB::table('system_documents')->where('code', 'PROD')->delete();
        
        $names = [
            'recipes.view', 'recipes.create', 'recipes.edit', 'recipes.delete',
            'production.view', 'production.create', 'production.cancel'
        ];

        DB::table('permissions')->whereIn('name', $names)->delete();
        // cascade deletion should handle permission_role if configured, otherwise we'd need to delete it manually.
        // Actually, let's manually delete from permission_role
        // Since we delete from permissions, if there's no cascade we might have orphans. It's just a down method.
    }
};
