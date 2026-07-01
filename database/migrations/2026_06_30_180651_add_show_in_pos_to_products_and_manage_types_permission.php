<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'show_in_pos')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('show_in_pos')->default(true)->after('show_in_shop');
            });
        }

        // Add the permission
        $moduleId = DB::table('modules')->where('name', 'products')->value('id');
        
        if ($moduleId) {
            DB::table('permissions')->insertOrIgnore([
                'module_id' => $moduleId,
                'name' => 'products.manage_types',
                'display_name' => 'Gestionar Tipos de Producto',
                'description' => 'Permite clasificar un producto como Insumo o Producto Terminado.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Assign to admin role
            $adminRole = DB::table('roles')->where('name', 'Administrador del Sistema')->first();
            $permission = DB::table('permissions')->where('name', 'products.manage_types')->first();
            
            if ($adminRole && $permission) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('show_in_pos');
        });
        
        DB::table('permissions')->where('name', 'products.manage_types')->delete();
    }
};
