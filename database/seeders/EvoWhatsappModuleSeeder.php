<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class EvoWhatsappModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create module
        $module = Module::firstOrCreate(
            ['name' => 'evo_whatsapp'],
            [
                'display_name' => 'Evo WhatsApp',
                'icon' => 'chat-bubble-left-right',
                'is_active' => true,
            ]
        );

        // Create permissions
        $permissions = [
            ['name' => 'evo_whatsapp.view', 'display_name' => 'Ver configuración de Evo WhatsApp', 'module_id' => $module->id],
            ['name' => 'evo_whatsapp.edit', 'display_name' => 'Editar configuración de Evo WhatsApp', 'module_id' => $module->id],
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
