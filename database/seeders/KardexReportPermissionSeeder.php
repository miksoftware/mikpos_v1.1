<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class KardexReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get reports module
        $reportsModule = Module::where('name', 'reports')->first();

        if (!$reportsModule) {
            return;
        }

        // Create kardex permission if not exists
        $permission = Permission::firstOrCreate(
            ['name' => 'reports.kardex'],
            [
                'display_name' => 'Reporte Kardex',
                'description' => 'Ver reporte de kardex de inventario',
                'module_id' => $reportsModule->id,
            ]
        );

        // Assign to super_admin and branch_admin roles
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin', 'supervisor'])->get();
        
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
