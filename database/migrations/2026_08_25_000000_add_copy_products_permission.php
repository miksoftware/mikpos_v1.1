<?php

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get or create the branches module
        $branchesModule = Module::firstOrCreate(
            ['name' => 'branches'],
            [
                'display_name' => 'Sucursales',
                'icon' => 'building',
                'order' => 2,
                'is_active' => true,
            ]
        );

        // 2. Create the copy_products permission
        $permission = Permission::firstOrCreate(
            ['name' => 'branches.copy_products'],
            [
                'module_id' => $branchesModule->id,
                'display_name' => 'Copiar Productos',
                'description' => 'Permite copiar el catálogo de productos de una sucursal a otra',
            ]
        );

        // 3. Assign permission to super_admin and admin roles if they exist
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin && !$superAdmin->permissions()->where('permissions.id', $permission->id)->exists()) {
            $superAdmin->permissions()->attach($permission->id);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin && !$admin->permissions()->where('permissions.id', $permission->id)->exists()) {
            $admin->permissions()->attach($permission->id);
        }

        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin && !$branchAdmin->permissions()->where('permissions.id', $permission->id)->exists()) {
            $branchAdmin->permissions()->attach($permission->id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'branches.copy_products')->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};
