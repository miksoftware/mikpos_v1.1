<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ReportsModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create reports module
        $module = Module::firstOrCreate(
            ['name' => 'reports'],
            [
                'display_name' => 'Reportes',
                'icon' => 'chart-bar',
                'order' => 25,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'reports.view', 'display_name' => 'Ver Reportes', 'description' => 'Acceso al módulo de reportes'],
            ['name' => 'reports.products_sold', 'display_name' => 'Reporte Productos Vendidos', 'description' => 'Ver reporte de productos vendidos'],
            ['name' => 'reports.commissions', 'display_name' => 'Reporte Comisiones', 'description' => 'Ver reporte de comisiones'],
            ['name' => 'reports.export', 'display_name' => 'Exportar Reportes', 'description' => 'Exportar reportes a PDF/Excel'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'display_name' => $permData['display_name'],
                    'description' => $permData['description'],
                    'module_id' => $module->id,
                ]
            );
        }

        // Assign permissions to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('module_id', $module->id)->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign permissions to branch_admin role
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $permissionIds = Permission::where('module_id', $module->id)->pluck('id');
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign view permission to supervisor role
        $supervisor = Role::where('name', 'supervisor')->first();
        if ($supervisor) {
            $viewPermission = Permission::where('name', 'reports.view')->first();
            $productsSoldPermission = Permission::where('name', 'reports.products_sold')->first();
            $commissionsPermission = Permission::where('name', 'reports.commissions')->first();
            if ($viewPermission) {
                $supervisor->permissions()->syncWithoutDetaching([$viewPermission->id]);
            }
            if ($productsSoldPermission) {
                $supervisor->permissions()->syncWithoutDetaching([$productsSoldPermission->id]);
            }
            if ($commissionsPermission) {
                $supervisor->permissions()->syncWithoutDetaching([$commissionsPermission->id]);
            }
        }
    }
}
