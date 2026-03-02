<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class ExpensesModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'expenses'],
            [
                'display_name' => 'Gastos',
                'icon' => 'receipt-refund',
                'order' => 36,
            ]
        );

        $permissions = [
            ['name' => 'expenses.view', 'display_name' => 'Ver gastos', 'module_id' => $module->id],
            ['name' => 'expenses.create', 'display_name' => 'Crear gastos', 'module_id' => $module->id],
            ['name' => 'expenses.edit', 'display_name' => 'Editar gastos', 'module_id' => $module->id],
            ['name' => 'expenses.delete', 'display_name' => 'Eliminar gastos', 'module_id' => $module->id],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();

        $permissionIds = Permission::where('name', 'like', 'expenses.%')->pluck('id');

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
