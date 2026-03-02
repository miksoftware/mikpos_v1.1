<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DiscountsModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'discounts'],
            ['display_name' => 'Descuentos', 'icon' => 'tag', 'order' => 35, 'is_active' => true]
        );

        $permissions = [
            ['name' => 'discounts.view', 'display_name' => 'Ver descuentos', 'description' => 'Ver listado de descuentos'],
            ['name' => 'discounts.create', 'display_name' => 'Crear descuentos', 'description' => 'Crear nuevos descuentos'],
            ['name' => 'discounts.edit', 'display_name' => 'Editar descuentos', 'description' => 'Editar descuentos existentes'],
            ['name' => 'discounts.delete', 'display_name' => 'Eliminar descuentos', 'description' => 'Eliminar descuentos'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['display_name' => $perm['display_name'], 'description' => $perm['description'], 'module_id' => $module->id]
            );
        }

        // Assign to super_admin and branch_admin
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        $permIds = Permission::where('name', 'like', 'discounts.%')->pluck('id');

        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching($permIds);
        }
    }
}
