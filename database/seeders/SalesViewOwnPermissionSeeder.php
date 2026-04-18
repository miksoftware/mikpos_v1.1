<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SalesViewOwnPermissionSeeder extends Seeder
{
    /**
     * Add "view own sales" permission to the sales module.
     */
    public function run(): void
    {
        $module = Module::where('name', 'sales')->first();

        if (!$module) {
            return;
        }

        $permission = Permission::firstOrCreate(
            ['name' => 'sales.view_own'],
            [
                'module_id' => $module->id,
                'display_name' => 'Ver solo mis ventas',
                'description' => 'Limita la vista de ventas a solo las realizadas por el usuario',
            ]
        );

        // Assign to cashier role by default (they typically should only see their own sales)
        $cashierRole = Role::where('name', 'cashier')->first();
        if ($cashierRole) {
            $cashierRole->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
