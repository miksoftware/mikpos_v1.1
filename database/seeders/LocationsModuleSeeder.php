<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class LocationsModuleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Locations module (CRUD for location master data) ──────────────────
        $locModule = Module::firstOrCreate(
            ['name' => 'locations'],
            ['display_name' => 'Ubicaciones', 'icon' => 'map-pin', 'order' => 36, 'is_active' => true]
        );

        $locPermissions = [
            ['name' => 'locations.view',   'display_name' => 'Ver ubicaciones',      'description' => 'Ver listado de ubicaciones'],
            ['name' => 'locations.create', 'display_name' => 'Crear ubicaciones',    'description' => 'Crear nuevas ubicaciones'],
            ['name' => 'locations.edit',   'display_name' => 'Editar ubicaciones',   'description' => 'Editar ubicaciones existentes'],
            ['name' => 'locations.delete', 'display_name' => 'Eliminar ubicaciones', 'description' => 'Eliminar ubicaciones'],
        ];

        $locPermIds = [];
        foreach ($locPermissions as $perm) {
            $p = Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['display_name' => $perm['display_name'], 'description' => $perm['description'], 'module_id' => $locModule->id]
            );
            $locPermIds[] = $p->id;
        }

        // ── Location transfers module ─────────────────────────────────────────
        $ltModule = Module::firstOrCreate(
            ['name' => 'location_transfers'],
            ['display_name' => 'Traslados de Ubicación', 'icon' => 'arrows-right-left', 'order' => 37, 'is_active' => true]
        );

        $ltPermissions = [
            ['name' => 'location_transfers.view',   'display_name' => 'Ver traslados de ubicación',      'description' => 'Ver listado de traslados de ubicación'],
            ['name' => 'location_transfers.create', 'display_name' => 'Crear traslados de ubicación',    'description' => 'Crear nuevos traslados de ubicación'],
            ['name' => 'location_transfers.delete', 'display_name' => 'Eliminar traslados de ubicación', 'description' => 'Eliminar traslados de ubicación'],
        ];

        $ltPermIds = [];
        foreach ($ltPermissions as $perm) {
            $p = Permission::firstOrCreate(
                ['name' => $perm['name']],
                ['display_name' => $perm['display_name'], 'description' => $perm['description'], 'module_id' => $ltModule->id]
            );
            $ltPermIds[] = $p->id;
        }

        // Assign both sets to super_admin and branch_admin
        $roles = Role::whereIn('name', ['super_admin', 'branch_admin'])->get();
        foreach ($roles as $role) {
            $role->permissions()->syncWithoutDetaching(array_merge($locPermIds, $ltPermIds));
        }
    }
}
