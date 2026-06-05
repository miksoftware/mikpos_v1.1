<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PromotionsModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create module (skip if already exists)
        $module = Module::firstOrCreate(
            ['name' => 'promotions'],
            [
                'display_name' => 'Ofertas y Promociones',
                'icon'         => 'megaphone',
                'order'        => 23,
            ]
        );

        // Create permissions (skip if already exist)
        $permissions = [
            ['name' => 'promotions.view',   'display_name' => 'Ver Campañas'],
            ['name' => 'promotions.create', 'display_name' => 'Crear Campañas'],
            ['name' => 'promotions.edit',   'display_name' => 'Editar Campañas'],
            ['name' => 'promotions.delete', 'display_name' => 'Eliminar Campañas'],
            ['name' => 'promotions.send',   'display_name' => 'Enviar Campañas'],
        ];

        foreach ($permissions as $perm) {
            $module->permissions()->firstOrCreate(
                ['name' => $perm['name']],
                ['display_name' => $perm['display_name']]
            );
        }

        // Assign all promotions permissions to super_admin and branch_admin roles
        $allPromoPermissions = Permission::where('name', 'like', 'promotions.%')->pluck('id');

        $superAdmin  = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($allPromoPermissions);
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($allPromoPermissions);
        }
    }
}
