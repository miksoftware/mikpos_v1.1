<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PosSellerPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates pos.change_seller permission.
     */
    public function run(): void
    {
        $module = Module::where('name', 'pos')->first();

        if (!$module) {
            return;
        }

        $permission = Permission::firstOrCreate(
            ['name' => 'pos.change_seller'],
            [
                'display_name' => 'Cambiar Vendedor en POS',
                'description' => 'Permite seleccionar o cambiar manualmente el vendedor al realizar una venta en el POS',
                'module_id' => $module->id,
            ]
        );

        // Assign by default to super_admin and branch_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin && $permission) {
            $superAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin && $permission) {
            $branchAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
