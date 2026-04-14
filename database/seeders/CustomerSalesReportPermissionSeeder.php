<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerSalesReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'reports'],
            ['display_name' => 'Reportes', 'icon' => 'chart-bar', 'order' => 20, 'is_active' => true]
        );

        $permission = Permission::firstOrCreate(
            ['name' => 'reports.customer_sales'],
            [
                'display_name' => 'Reporte de Ventas por Cliente',
                'description' => 'Ver reporte de ventas por cliente',
                'module_id' => $module->id,
            ]
        );

        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
