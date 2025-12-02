<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductCatalogPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name' => 'categories',
                'display_name' => 'Categorías',
                'icon' => 'folder',
                'order' => 14,
                'permissions' => [
                    ['name' => 'categories.view', 'display_name' => 'Ver Categorías'],
                    ['name' => 'categories.create', 'display_name' => 'Crear Categorías'],
                    ['name' => 'categories.edit', 'display_name' => 'Editar Categorías'],
                    ['name' => 'categories.delete', 'display_name' => 'Eliminar Categorías'],
                ],
            ],
            [
                'name' => 'subcategories',
                'display_name' => 'Subcategorías',
                'icon' => 'folder-open',
                'order' => 15,
                'permissions' => [
                    ['name' => 'subcategories.view', 'display_name' => 'Ver Subcategorías'],
                    ['name' => 'subcategories.create', 'display_name' => 'Crear Subcategorías'],
                    ['name' => 'subcategories.edit', 'display_name' => 'Editar Subcategorías'],
                    ['name' => 'subcategories.delete', 'display_name' => 'Eliminar Subcategorías'],
                ],
            ],
            [
                'name' => 'brands',
                'display_name' => 'Marcas',
                'icon' => 'tag',
                'order' => 16,
                'permissions' => [
                    ['name' => 'brands.view', 'display_name' => 'Ver Marcas'],
                    ['name' => 'brands.create', 'display_name' => 'Crear Marcas'],
                    ['name' => 'brands.edit', 'display_name' => 'Editar Marcas'],
                    ['name' => 'brands.delete', 'display_name' => 'Eliminar Marcas'],
                ],
            ],
            [
                'name' => 'units',
                'display_name' => 'Unidades de Medida',
                'icon' => 'scale',
                'order' => 17,
                'permissions' => [
                    ['name' => 'units.view', 'display_name' => 'Ver Unidades'],
                    ['name' => 'units.create', 'display_name' => 'Crear Unidades'],
                    ['name' => 'units.edit', 'display_name' => 'Editar Unidades'],
                    ['name' => 'units.delete', 'display_name' => 'Eliminar Unidades'],
                ],
            ],
            [
                'name' => 'product_models',
                'display_name' => 'Modelos',
                'icon' => 'cube',
                'order' => 18,
                'permissions' => [
                    ['name' => 'product_models.view', 'display_name' => 'Ver Modelos'],
                    ['name' => 'product_models.create', 'display_name' => 'Crear Modelos'],
                    ['name' => 'product_models.edit', 'display_name' => 'Editar Modelos'],
                    ['name' => 'product_models.delete', 'display_name' => 'Eliminar Modelos'],
                ],
            ],
            [
                'name' => 'presentations',
                'display_name' => 'Presentaciones',
                'icon' => 'gift',
                'order' => 19,
                'permissions' => [
                    ['name' => 'presentations.view', 'display_name' => 'Ver Presentaciones'],
                    ['name' => 'presentations.create', 'display_name' => 'Crear Presentaciones'],
                    ['name' => 'presentations.edit', 'display_name' => 'Editar Presentaciones'],
                    ['name' => 'presentations.delete', 'display_name' => 'Eliminar Presentaciones'],
                ],
            ],
            [
                'name' => 'colors',
                'display_name' => 'Colores',
                'icon' => 'palette',
                'order' => 20,
                'permissions' => [
                    ['name' => 'colors.view', 'display_name' => 'Ver Colores'],
                    ['name' => 'colors.create', 'display_name' => 'Crear Colores'],
                    ['name' => 'colors.edit', 'display_name' => 'Editar Colores'],
                    ['name' => 'colors.delete', 'display_name' => 'Eliminar Colores'],
                ],
            ],
            [
                'name' => 'imeis',
                'display_name' => 'IMEIs',
                'icon' => 'device-mobile',
                'order' => 21,
                'permissions' => [
                    ['name' => 'imeis.view', 'display_name' => 'Ver IMEIs'],
                    ['name' => 'imeis.create', 'display_name' => 'Crear IMEIs'],
                    ['name' => 'imeis.edit', 'display_name' => 'Editar IMEIs'],
                    ['name' => 'imeis.delete', 'display_name' => 'Eliminar IMEIs'],
                ],
            ],
        ];

        $newPermissionIds = [];

        foreach ($modules as $moduleData) {
            $permissions = $moduleData['permissions'];
            unset($moduleData['permissions']);

            $module = Module::firstOrCreate(
                ['name' => $moduleData['name']],
                $moduleData
            );

            foreach ($permissions as $permissionData) {
                $permission = $module->permissions()->firstOrCreate(
                    ['name' => $permissionData['name']],
                    $permissionData
                );
                $newPermissionIds[] = $permission->id;
            }
        }

        // Assign new permissions to super_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($newPermissionIds);
        }

        // Assign to branch_admin
        $branchAdmin = Role::where('name', 'branch_admin')->first();
        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($newPermissionIds);
        }
    }
}
