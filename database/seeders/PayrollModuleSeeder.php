<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PayrollModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create employees module
        $employeesModule = Module::firstOrCreate(
            ['name' => 'employees'],
            ['display_name' => 'Empleados', 'icon' => 'users', 'order' => 40, 'is_active' => true]
        );

        $employeePermissions = [
            ['name' => 'employees.view', 'display_name' => 'Ver Empleados'],
            ['name' => 'employees.create', 'display_name' => 'Crear Empleados'],
            ['name' => 'employees.edit', 'display_name' => 'Editar Empleados'],
            ['name' => 'employees.delete', 'display_name' => 'Eliminar Empleados'],
        ];

        foreach ($employeePermissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                array_merge($perm, ['module_id' => $employeesModule->id])
            );
        }

        // Create payrolls module
        $payrollsModule = Module::firstOrCreate(
            ['name' => 'payrolls'],
            ['display_name' => 'Nómina', 'icon' => 'banknotes', 'order' => 41, 'is_active' => true]
        );

        $payrollPermissions = [
            ['name' => 'payrolls.view', 'display_name' => 'Ver Nómina'],
            ['name' => 'payrolls.create', 'display_name' => 'Crear Nómina'],
            ['name' => 'payrolls.edit', 'display_name' => 'Editar Nómina'],
            ['name' => 'payrolls.delete', 'display_name' => 'Eliminar Nómina'],
            ['name' => 'payrolls.approve', 'display_name' => 'Aprobar/Pagar Nómina'],
        ];

        foreach ($payrollPermissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                array_merge($perm, ['module_id' => $payrollsModule->id])
            );
        }

        // Assign to roles
        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();

        $allPermIds = Permission::whereIn('name', array_merge(
            array_column($employeePermissions, 'name'),
            array_column($payrollPermissions, 'name')
        ))->pluck('id');

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($allPermIds);
        }

        if ($branchAdmin) {
            // Branch admin gets all except approve
            $branchAdminPerms = Permission::whereIn('name', [
                'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
                'payrolls.view', 'payrolls.create', 'payrolls.edit',
            ])->pluck('id');
            $branchAdmin->permissions()->syncWithoutDetaching($branchAdminPerms);
        }
    }
}
