<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class PrintFormatsModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'print_formats'],
            [
                'display_name' => 'Formatos de Impresión',
                'icon' => 'printer',
                'is_active' => true,
            ]
        );

        $permissions = [
            ['name' => 'print_formats.view', 'display_name' => 'Ver formatos de impresión', 'module_id' => $module->id],
            ['name' => 'print_formats.edit', 'display_name' => 'Editar formatos de impresión', 'module_id' => $module->id],
        ];

        $permissionIds = [];
        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
            $permissionIds[] = $permission->id;
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
