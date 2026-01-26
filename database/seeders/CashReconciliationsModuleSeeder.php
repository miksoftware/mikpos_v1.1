<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CashReconciliationsModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'cash_reconciliations'],
            ['display_name' => 'Arqueos de Caja', 'is_active' => true]
        );

        // Create permissions
        $permissions = [
            ['name' => 'cash_reconciliations.view', 'display_name' => 'Ver arqueos de caja', 'description' => 'Ver listado de arqueos de caja'],
            ['name' => 'cash_reconciliations.create', 'display_name' => 'Abrir caja', 'description' => 'Abrir cajas para operar'],
            ['name' => 'cash_reconciliations.edit', 'display_name' => 'Cerrar caja', 'description' => 'Cerrar cajas abiertas'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name']],
                array_merge($permData, ['module_id' => $module->id])
            );
        }

        // Assign to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('name', 'like', 'cash_reconciliations.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign to branch_admin role
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $permissionIds = Permission::where('name', 'like', 'cash_reconciliations.%')->pluck('id');
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign to cashier role (they need to open/close their own cash register)
        $cashier = Role::where('name', 'cashier')->first();
        if ($cashier) {
            $permissionIds = Permission::where('name', 'like', 'cash_reconciliations.%')->pluck('id');
            $cashier->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
