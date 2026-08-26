<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductMergePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::where('name', 'products')->first();
        if (!$module) {
            return;
        }

        $permission = Permission::firstOrCreate(
            ['name' => 'products.merge'],
            [
                'display_name' => 'Unificar Productos Duplicados',
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
