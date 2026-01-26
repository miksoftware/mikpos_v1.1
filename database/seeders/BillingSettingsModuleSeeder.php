<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class BillingSettingsModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'billing_settings'],
            [
                'display_name' => 'Facturación Electrónica',
                'icon' => 'document-text',
                'is_active' => true,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'billing_settings.view', 'display_name' => 'Ver configuración de facturación', 'module_id' => $module->id],
            ['name' => 'billing_settings.edit', 'display_name' => 'Editar configuración de facturación', 'module_id' => $module->id],
        ];

        $permissionIds = [];
        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
            $permissionIds[] = $permission->id;
        }

        // Assign permissions to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
