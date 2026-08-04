<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductImportFieldsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::where('name', 'products')->first();
        if (!$module) {
            return;
        }

        $permission = Permission::firstOrCreate(
            ['name' => 'products.manage_import'],
            [
                'display_name' => 'Gestionar Datos de Importación',
                'module_id' => $module->id,
            ]
        );

        foreach (['super_admin', 'branch_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }
    }
}
