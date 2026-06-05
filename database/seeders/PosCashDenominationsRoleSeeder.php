<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PosCashDenominationsRoleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::where('name', 'pos')->first();

        if (!$module) {
            return;
        }

        $permission = Permission::firstOrCreate(
            ['name' => 'pos.cash_denominations'],
            [
                'display_name' => 'Ver Billetes y Monedas al Pagar',
                'description' => 'Permite ver el panel de billetes y monedas para calcular el pago recibido',
                'module_id' => $module->id,
            ]
        );

        // Assign to super_admin, branch_admin and cashier roles by default
        $roles = \App\Models\Role::whereIn('name', ['super_admin', 'branch_admin', 'cashier'])->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
