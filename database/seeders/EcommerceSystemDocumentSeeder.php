<?php

namespace Database\Seeders;

use App\Models\SystemDocument;
use Illuminate\Database\Seeder;

class EcommerceSystemDocumentSeeder extends Seeder
{
    public function run(): void
    {
        SystemDocument::firstOrCreate(
            ['code' => 'ecommerce-sale'],
            [
                'name' => 'Venta E-commerce',
                'prefix' => 'ECM',
                'description' => 'Documento para ventas desde la tienda en línea',
                'next_number' => 1,
                'is_active' => true,
            ]
        );
    }
}
