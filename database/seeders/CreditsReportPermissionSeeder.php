<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class CreditsReportPermissionSeeder extends Seeder
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
            ['name' => 'reports.credits'],
            [
                'display_name' => 'Ver Reporte de Créditos',
                'description' => 'Permite ver el reporte de créditos (cuentas por pagar y cobrar)',
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
