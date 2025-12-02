<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create modules with their permissions
        $modules = [
            [
                'name' => 'dashboard',
                'display_name' => 'Dashboard',
                'icon' => 'home',
                'order' => 1,
                'permissions' => [
                    ['name' => 'dashboard.view', 'display_name' => 'Ver Dashboard'],
                ],
            ],
            [
                'name' => 'branches',
                'display_name' => 'Sucursales',
                'icon' => 'building',
                'order' => 2,
                'permissions' => [
                    ['name' => 'branches.view', 'display_name' => 'Ver Sucursales'],
                    ['name' => 'branches.create', 'display_name' => 'Crear Sucursales'],
                    ['name' => 'branches.edit', 'display_name' => 'Editar Sucursales'],
                    ['name' => 'branches.delete', 'display_name' => 'Eliminar Sucursales'],
                ],
            ],
            [
                'name' => 'users',
                'display_name' => 'Usuarios',
                'icon' => 'users',
                'order' => 3,
                'permissions' => [
                    ['name' => 'users.view', 'display_name' => 'Ver Usuarios'],
                    ['name' => 'users.create', 'display_name' => 'Crear Usuarios'],
                    ['name' => 'users.edit', 'display_name' => 'Editar Usuarios'],
                    ['name' => 'users.delete', 'display_name' => 'Eliminar Usuarios'],
                ],
            ],
            [
                'name' => 'departments',
                'display_name' => 'Departamentos',
                'icon' => 'map',
                'order' => 4,
                'permissions' => [
                    ['name' => 'departments.view', 'display_name' => 'Ver Departamentos'],
                    ['name' => 'departments.create', 'display_name' => 'Crear Departamentos'],
                    ['name' => 'departments.edit', 'display_name' => 'Editar Departamentos'],
                    ['name' => 'departments.delete', 'display_name' => 'Eliminar Departamentos'],
                ],
            ],
            [
                'name' => 'municipalities',
                'display_name' => 'Municipios',
                'icon' => 'location',
                'order' => 5,
                'permissions' => [
                    ['name' => 'municipalities.view', 'display_name' => 'Ver Municipios'],
                    ['name' => 'municipalities.create', 'display_name' => 'Crear Municipios'],
                    ['name' => 'municipalities.edit', 'display_name' => 'Editar Municipios'],
                    ['name' => 'municipalities.delete', 'display_name' => 'Eliminar Municipios'],
                ],
            ],
            [
                'name' => 'roles',
                'display_name' => 'Roles y Permisos',
                'icon' => 'shield',
                'order' => 6,
                'permissions' => [
                    ['name' => 'roles.view', 'display_name' => 'Ver Roles'],
                    ['name' => 'roles.create', 'display_name' => 'Crear Roles'],
                    ['name' => 'roles.edit', 'display_name' => 'Editar Roles'],
                    ['name' => 'roles.delete', 'display_name' => 'Eliminar Roles'],
                    ['name' => 'roles.assign', 'display_name' => 'Asignar Roles'],
                ],
            ],
            [
                'name' => 'pos',
                'display_name' => 'Punto de Venta',
                'icon' => 'calculator',
                'order' => 7,
                'permissions' => [
                    ['name' => 'pos.access', 'display_name' => 'Acceder al POS'],
                    ['name' => 'pos.sell', 'display_name' => 'Realizar Ventas'],
                    ['name' => 'pos.discount', 'display_name' => 'Aplicar Descuentos'],
                    ['name' => 'pos.cancel', 'display_name' => 'Cancelar Ventas'],
                    ['name' => 'pos.reprint', 'display_name' => 'Reimprimir Tickets'],
                ],
            ],
            [
                'name' => 'reports',
                'display_name' => 'Reportes',
                'icon' => 'chart',
                'order' => 8,
                'permissions' => [
                    ['name' => 'reports.sales', 'display_name' => 'Ver Reportes de Ventas'],
                    ['name' => 'reports.inventory', 'display_name' => 'Ver Reportes de Inventario'],
                    ['name' => 'reports.users', 'display_name' => 'Ver Reportes de Usuarios'],
                    ['name' => 'reports.export', 'display_name' => 'Exportar Reportes'],
                ],
            ],
            [
                'name' => 'activity_logs',
                'display_name' => 'Logs de Actividad',
                'icon' => 'clipboard',
                'order' => 9,
                'permissions' => [
                    ['name' => 'activity_logs.view', 'display_name' => 'Ver Logs de Actividad'],
                    ['name' => 'activity_logs.export', 'display_name' => 'Exportar Logs'],
                ],
            ],
            [
                'name' => 'tax_documents',
                'display_name' => 'Documentos Tributarios',
                'icon' => 'document',
                'order' => 10,
                'permissions' => [
                    ['name' => 'tax_documents.view', 'display_name' => 'Ver Documentos Tributarios'],
                    ['name' => 'tax_documents.create', 'display_name' => 'Crear Documentos Tributarios'],
                    ['name' => 'tax_documents.edit', 'display_name' => 'Editar Documentos Tributarios'],
                    ['name' => 'tax_documents.delete', 'display_name' => 'Eliminar Documentos Tributarios'],
                ],
            ],
            [
                'name' => 'currencies',
                'display_name' => 'Monedas',
                'icon' => 'currency',
                'order' => 11,
                'permissions' => [
                    ['name' => 'currencies.view', 'display_name' => 'Ver Monedas'],
                    ['name' => 'currencies.create', 'display_name' => 'Crear Monedas'],
                    ['name' => 'currencies.edit', 'display_name' => 'Editar Monedas'],
                    ['name' => 'currencies.delete', 'display_name' => 'Eliminar Monedas'],
                ],
            ],
            [
                'name' => 'payment_methods',
                'display_name' => 'Medios de Pago',
                'icon' => 'credit-card',
                'order' => 12,
                'permissions' => [
                    ['name' => 'payment_methods.view', 'display_name' => 'Ver Medios de Pago'],
                    ['name' => 'payment_methods.create', 'display_name' => 'Crear Medios de Pago'],
                    ['name' => 'payment_methods.edit', 'display_name' => 'Editar Medios de Pago'],
                    ['name' => 'payment_methods.delete', 'display_name' => 'Eliminar Medios de Pago'],
                ],
            ],
            [
                'name' => 'taxes',
                'display_name' => 'Impuestos',
                'icon' => 'percent',
                'order' => 13,
                'permissions' => [
                    ['name' => 'taxes.view', 'display_name' => 'Ver Impuestos'],
                    ['name' => 'taxes.create', 'display_name' => 'Crear Impuestos'],
                    ['name' => 'taxes.edit', 'display_name' => 'Editar Impuestos'],
                    ['name' => 'taxes.delete', 'display_name' => 'Eliminar Impuestos'],
                ],
            ],
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

        foreach ($modules as $moduleData) {
            $permissions = $moduleData['permissions'];
            unset($moduleData['permissions']);

            $module = Module::create($moduleData);

            foreach ($permissions as $permissionData) {
                $module->permissions()->create($permissionData);
            }
        }

        // Create system roles
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Administrador General',
            'description' => 'Acceso total al sistema en todas las sucursales',
            'is_system' => true,
        ]);

        $branchAdmin = Role::create([
            'name' => 'branch_admin',
            'display_name' => 'Administrador de Sucursal',
            'description' => 'Administración completa de una sucursal específica',
            'is_system' => true,
        ]);

        $supervisor = Role::create([
            'name' => 'supervisor',
            'display_name' => 'Supervisor',
            'description' => 'Supervisión de operaciones y reportes',
            'is_system' => true,
        ]);

        $cashier = Role::create([
            'name' => 'cashier',
            'display_name' => 'Cajero',
            'description' => 'Operaciones básicas de punto de venta',
            'is_system' => true,
        ]);

        // Assign all permissions to super_admin
        $allPermissions = Permission::pluck('id');
        $superAdmin->permissions()->attach($allPermissions);

        // Assign permissions to branch_admin (all except roles management and activity logs export)
        $branchAdminPermissions = Permission::whereNotIn('name', [
            'roles.create', 'roles.edit', 'roles.delete',
            'activity_logs.export'
        ])->pluck('id');
        $branchAdmin->permissions()->attach($branchAdminPermissions);

        // Assign permissions to supervisor
        $supervisorPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'branches.view',
            'users.view',
            'pos.access', 'pos.sell', 'pos.discount', 'pos.cancel', 'pos.reprint',
            'reports.sales', 'reports.inventory',
        ])->pluck('id');
        $supervisor->permissions()->attach($supervisorPermissions);

        // Assign permissions to cashier
        $cashierPermissions = Permission::whereIn('name', [
            'dashboard.view',
            'pos.access', 'pos.sell',
        ])->pluck('id');
        $cashier->permissions()->attach($cashierPermissions);
    }
}
