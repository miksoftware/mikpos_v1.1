<?php

namespace Database\Seeders;

use App\Models\SystemDocument;
use Illuminate\Database\Seeder;

class QuoteReservationSystemDocumentSeeder extends Seeder
{
    public function run(): void
    {
        // System document used for inventory movements created when a quote reserves stock.
        // Movement type 'out': stock is reserved when quote is created.
        // A reversal 'in' movement (using code 'adjustment') is created when quote is
        // cancelled or its inventory reservation is released upon conversion to sale.
        SystemDocument::firstOrCreate(
            ['code' => 'quote_reservation'],
            [
                'name' => 'Reserva de Cotización',
                'prefix' => 'RCT',
                'description' => 'Movimiento de inventario para reserva de stock al crear una cotización',
                'next_number' => 1,
                'is_active' => true,
            ]
        );
    }
}
