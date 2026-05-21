<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RefundsReportPermissionSeeder extends Seeder
{
    /**
     * Seed the reports.refunds permission for the dedicated Refunds report.
     */
    public function run(): void
    {
        // Reuse existing 'reports' module
        $module = Module::firstOrCreate(
            ['name' => 'reports'],
            ['display_name' => 'Reportes', 'icon' => 'chart-bar', 'is_active' => true]
        );

        $permission = Permission::firstOrCreate(
            ['name' => 'reports.refunds'],
            [
                'module_id' => $module->id,
                'display_name' => 'Reporte de Devoluciones',
                'description' => 'Ver el reporte de devoluciones y notas crédito',
            ]
        );

        // Assign to super_admin and branch_admin
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
