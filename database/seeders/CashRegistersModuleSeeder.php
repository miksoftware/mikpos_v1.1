<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CashRegistersModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'cash_registers'],
            ['display_name' => 'Cajas', 'is_active' => true]
        );

        // Create permissions
        $permissions = [
            ['name' => 'cash_registers.view', 'display_name' => 'Ver cajas', 'description' => 'Ver listado de cajas'],
            ['name' => 'cash_registers.create', 'display_name' => 'Crear cajas', 'description' => 'Crear nuevas cajas'],
            ['name' => 'cash_registers.edit', 'display_name' => 'Editar cajas', 'description' => 'Editar cajas existentes'],
            ['name' => 'cash_registers.delete', 'display_name' => 'Eliminar cajas', 'description' => 'Eliminar cajas'],
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
            $permissionIds = Permission::where('name', 'like', 'cash_registers.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign to branch_admin role
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $permissionIds = Permission::where('name', 'like', 'cash_registers.%')->pluck('id');
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
