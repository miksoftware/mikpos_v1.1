<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CommissionsReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get reports module
        $module = Module::where('name', 'reports')->first();
        
        if (!$module) {
            return;
        }

        // Create commissions permission if not exists
        $permission = Permission::firstOrCreate(
            ['name' => 'reports.commissions'],
            [
                'display_name' => 'Reporte Comisiones',
                'description' => 'Ver reporte de comisiones',
                'module_id' => $module->id,
            ]
        );

        // Assign to super_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }

        // Assign to branch_admin
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }

        // Assign to supervisor
        $supervisor = Role::where('name', 'supervisor')->first();
        if ($supervisor) {
            $supervisor->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
