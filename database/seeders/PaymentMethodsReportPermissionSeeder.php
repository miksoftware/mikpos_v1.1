<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PaymentMethodsReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'reports'],
            ['display_name' => 'Reportes', 'icon' => 'chart-bar', 'order' => 20, 'is_active' => true]
        );

        $permission = Permission::firstOrCreate(
            ['name' => 'reports.payment_methods'],
            [
                'display_name' => 'Reporte de Medios de Pago',
                'description' => 'Ver reporte de medios de pago',
                'module_id' => $module->id,
            ]
        );

        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
