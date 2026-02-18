<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MigrationModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'migration'],
            [
                'display_name' => 'Migración de Datos',
                'icon' => 'arrow-path',
                'order' => 99,
                'is_active' => true,
            ]
        );

        $permission = Permission::firstOrCreate(
            ['name' => 'migration.view'],
            [
                'display_name' => 'Ver Migración',
                'description' => 'Acceso al módulo de migración de datos',
                'module_id' => $module->id,
            ]
        );

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
