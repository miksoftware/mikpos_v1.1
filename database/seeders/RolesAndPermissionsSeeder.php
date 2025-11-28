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
                'name' => 'roles',
                'display_name' => 'Roles y Permisos',
                'icon' => 'shield',
                'order' => 4,
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
                'order' => 5,
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
                'order' => 6,
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
                'order' => 7,
                'permissions' => [
                    ['name' => 'activity_logs.view', 'display_name' => 'Ver Logs de Actividad'],
                    ['name' => 'activity_logs.export', 'display_name' => 'Exportar Logs'],
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
