<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CreditsModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'credits'],
            [
                'display_name' => 'Créditos y Pagos',
                'icon' => 'credit-card',
                'order' => 55,
                'is_active' => true,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'credits.view', 'display_name' => 'Ver créditos', 'description' => 'Ver listado de créditos y cuentas por pagar/cobrar'],
            ['name' => 'credits.pay', 'display_name' => 'Registrar pagos', 'description' => 'Registrar abonos y pagos de créditos'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                [
                    'display_name' => $perm['display_name'],
                    'description' => $perm['description'],
                    'module_id' => $module->id,
                ]
            );
        }

        // Assign to super_admin and branch_admin
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        $permissionIds = Permission::where('name', 'like', 'credits.%')->pluck('id');

        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Assign view only to supervisor
        $supervisor = Role::where('name', 'supervisor')->first();
        if ($supervisor) {
            $viewPermission = Permission::where('name', 'credits.view')->first();
            if ($viewPermission) {
                $supervisor->permissions()->syncWithoutDetaching([$viewPermission->id]);
            }
        }
    }
}
