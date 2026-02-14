<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CashReconciliationEditPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::where('name', 'cash_reconciliations')->first();

        if (!$module) {
            return;
        }

        Permission::firstOrCreate(
            ['name' => 'cash_reconciliations.edit_closed'],
            [
                'display_name' => 'Editar arqueo cerrado',
                'description' => 'Editar arqueos de caja ya cerrados con historial de cambios',
                'module_id' => $module->id,
            ]
        );

        // Assign to super_admin and branch_admin only
        foreach (['super_admin', 'branch_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $permId = Permission::where('name', 'cash_reconciliations.edit_closed')->pluck('id');
                $role->permissions()->syncWithoutDetaching($permId);
            }
        }
    }
}
