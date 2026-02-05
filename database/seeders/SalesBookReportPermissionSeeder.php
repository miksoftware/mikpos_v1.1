<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class SalesBookReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create reports module
        $reportsModule = Module::firstOrCreate(
            ['name' => 'reports'],
            [
                'display_name' => 'Reportes',
                'icon' => 'chart-bar',
                'is_active' => true,
            ]
        );

        // Create sales book permission
        $permission = Permission::firstOrCreate(
            ['name' => 'reports.sales_book'],
            [
                'display_name' => 'Ver Libro de Ventas',
                'description' => 'Permite ver el reporte de libro de ventas',
                'module_id' => $reportsModule->id,
            ]
        );

        // Assign to super_admin and branch_admin roles
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        foreach ($roles as $role) {
            if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
                $role->permissions()->attach($permission->id);
            }
        }
    }
}
