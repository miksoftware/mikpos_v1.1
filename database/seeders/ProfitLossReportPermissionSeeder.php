<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class ProfitLossReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $reportsModule = Module::firstOrCreate(
            ['name' => 'reports'],
            [
                'display_name' => 'Reportes',
                'icon' => 'chart-bar',
                'is_active' => true,
            ]
        );

        $permission = Permission::firstOrCreate(
            ['name' => 'reports.profit_loss'],
            [
                'display_name' => 'Ver Reporte P&G',
                'description' => 'Permite ver el reporte de pérdidas y ganancias',
                'module_id' => $reportsModule->id,
            ]
        );

        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        foreach ($roles as $role) {
            if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
                $role->permissions()->attach($permission->id);
            }
        }
    }
}
