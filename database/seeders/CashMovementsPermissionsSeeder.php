<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CashMovementsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Use the existing module
        $module = Module::firstOrCreate(
            ['name' => 'cash_reconciliations'],
            ['display_name' => 'Arqueos de Caja', 'is_active' => true]
        );

        // Create permissions
        $permissions = [
            ['name' => 'cash_movements.view', 'display_name' => 'Ver movimientos de caja', 'description' => 'Ver listado de ingresos y egresos de caja'],
            ['name' => 'cash_movements.edit', 'display_name' => 'Editar movimientos de caja', 'description' => 'Editar ingresos y egresos de caja abiertos'],
            ['name' => 'cash_movements.delete', 'display_name' => 'Eliminar movimientos de caja', 'description' => 'Eliminar ingresos y egresos de caja abiertos'],
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
            $permissionIds = Permission::where('name', 'like', 'cash_movements.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign to branch_admin role
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $permissionIds = Permission::where('name', 'like', 'cash_movements.%')->pluck('id');
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign to cashier role
        $cashier = Role::where('name', 'cashier')->first();
        if ($cashier) {
            $permissionIds = Permission::where('name', 'like', 'cash_movements.%')->pluck('id');
            $cashier->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
