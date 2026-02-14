<?php

namespace Database\Seeders;

use App\Models\SystemDocument;
use Illuminate\Database\Seeder;

class RefundSystemDocumentSeeder extends Seeder
{
    public function run(): void
    {
        SystemDocument::firstOrCreate(
            ['code' => 'refund'],
            [
                'name' => 'Devolución',
                'prefix' => 'DEV',
                'description' => 'Documento para devoluciones de ventas',
                'next_number' => 1,
                'is_active' => true,
            ]
        );
    }
}
