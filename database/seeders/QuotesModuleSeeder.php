<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class QuotesModuleSeeder extends Seeder
{
    /**
     * Seed the quotes module: module, permissions, role assignments,
     * and the print-format setting for quote receipts.
     */
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'quotes'],
            [
                'display_name' => 'Cotizaciones',
                'icon' => 'document-text',
                'is_active' => true,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'quotes.view', 'display_name' => 'Ver cotizaciones', 'description' => 'Ver listado de cotizaciones'],
            ['name' => 'quotes.create', 'display_name' => 'Crear cotizaciones', 'description' => 'Crear nuevas cotizaciones'],
            ['name' => 'quotes.delete', 'display_name' => 'Cancelar cotizaciones', 'description' => 'Cancelar cotizaciones en borrador'],
            ['name' => 'quotes.convert', 'display_name' => 'Convertir cotización a venta', 'description' => 'Convertir una cotización en venta desde el POS'],
        ];

        $permissionIds = [];
        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'module_id' => $module->id,
                    'display_name' => $permData['display_name'],
                    'description' => $permData['description'],
                ]
            );
            $permissionIds[] = $permission->id;
        }

        // Initially only super_admin and branch_admin get the permissions.
        // Cashiers and supervisors must be granted manually.
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Register print format setting for quote document type
        if (\Illuminate\Support\Facades\Schema::hasTable('print_format_settings')) {
            \App\Models\PrintFormatSetting::firstOrCreate(
                ['document_type' => 'quote'],
                [
                    'display_name' => 'Cotizaciones',
                    'format' => '80mm',
                    'letter_options' => \App\Models\PrintFormatSetting::DEFAULT_LETTER_OPTIONS,
                    'open_cash_drawer_on_skip' => false,
                    'show_logo_80mm' => false,
                ]
            );
        }
    }
}
