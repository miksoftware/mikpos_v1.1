<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PosCashDenominationsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::where('name', 'pos')->first();

        if (!$module) {
            return;
        }

        Permission::firstOrCreate(
            ['name' => 'pos.cash_denominations'],
            [
                'display_name' => 'Ver Billetes y Monedas al Pagar',
                'description' => 'Permite ver el panel de billetes y monedas para calcular el pago recibido',
                'module_id' => $module->id,
            ]
        );

        // Permission is NOT assigned to any role by default.
        // Each user/admin enables it manually from the Roles module.
    }
}
