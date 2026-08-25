<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ElectronicPayrollModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create electronic_payroll module
        $module = Module::firstOrCreate(
            ['name' => 'electronic_payroll'],
            ['display_name' => 'Nómina Electrónica', 'icon' => 'document-text', 'order' => 42, 'is_active' => true]
        );

        $permissions = [
            ['name' => 'electronic_payroll.view', 'display_name' => 'Ver Nómina Electrónica'],
            ['name' => 'electronic_payroll.edit', 'display_name' => 'Configurar Nómina Electrónica'],
            ['name' => 'electronic_payroll.transmit', 'display_name' => 'Transmitir Nómina a la DIAN'],
            ['name' => 'electronic_payroll.adjust', 'display_name' => 'Emitir Notas de Ajuste de Nómina'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                array_merge($perm, ['module_id' => $module->id])
            );
        }

        // Assign permissions to roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();

        $allPermIds = Permission::whereIn('name', array_column($permissions, 'name'))->pluck('id');

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($allPermIds);
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($allPermIds);
        }
    }
}
