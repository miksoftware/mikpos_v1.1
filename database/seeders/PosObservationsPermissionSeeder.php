<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PosObservationsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates pos.observations permission.
     * By default, it is NOT assigned to any role (remains deactivated until manually enabled in roles).
     */
    public function run(): void
    {
        $module = Module::where('name', 'pos')->first();

        if (!$module) {
            return;
        }

        Permission::firstOrCreate(
            ['name' => 'pos.observations'],
            [
                'display_name' => 'Observaciones en Venta POS',
                'description' => 'Permite ingresar observaciones o notas opcionales al procesar una venta en el POS',
                'module_id' => $module->id,
            ]
        );
    }
}
