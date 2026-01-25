<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemDocument;
use Illuminate\Database\Seeder;

class SystemDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        // Create default system documents
        $documents = [
            [
                'code' => 'purchase',
                'name' => 'Compra',
                'prefix' => 'CMP',
                'description' => 'Documento para registro de compras a proveedores',
            ],
            [
                'code' => 'initial_stock',
                'name' => 'Stock Inicial',
                'prefix' => 'STI',
                'description' => 'Documento para registro de inventario inicial de productos',
            ],
            [
                'code' => 'adjustment',
                'name' => 'Ajuste de Inventario',
                'prefix' => 'AJU',
                'description' => 'Documento para ajustes de inventario (entradas/salidas)',
            ],
            [
                'code' => 'transfer',
                'name' => 'Traslado a Sucursal',
                'prefix' => 'TRA',
                'description' => 'Documento para traslados de inventario entre sucursales',
            ],
            [
                'code' => 'sale',
                'name' => 'Venta',
                'prefix' => 'VTA',
                'description' => 'Documento para registro de ventas',
            ],
        ];

        foreach ($documents as $doc) {
            SystemDocument::firstOrCreate(
                ['code' => $doc['code']],
                array_merge($doc, ['next_number' => 1, 'is_active' => true])
            );
        }

        // Create module and permissions
        $module = Module::firstOrCreate(
            ['name' => 'system_documents'],
            ['display_name' => 'Documentos Sistema', 'icon' => 'document-text', 'order' => 50, 'is_active' => true]
        );

        $permissions = [
            ['name' => 'system_documents.view', 'display_name' => 'Ver documentos sistema', 'description' => 'Ver listado de documentos del sistema'],
            ['name' => 'system_documents.create', 'display_name' => 'Crear documentos sistema', 'description' => 'Crear nuevos documentos del sistema'],
            ['name' => 'system_documents.edit', 'display_name' => 'Editar documentos sistema', 'description' => 'Editar documentos del sistema existentes'],
            ['name' => 'system_documents.delete', 'display_name' => 'Eliminar documentos sistema', 'description' => 'Eliminar documentos del sistema'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                array_merge($perm, ['module_id' => $module->id])
            );
        }

        // Assign permissions to super_admin role
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $permissionIds = Permission::where('name', 'like', 'system_documents.%')->pluck('id');
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
