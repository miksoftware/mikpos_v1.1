<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('print_format_settings')->insertOrIgnore([
            'document_type' => 'ecommerce',
            'display_name'  => 'Pedido Tienda en Línea',
            'format'        => '80mm',
            'show_logo_80mm' => false,
            'open_cash_drawer_on_skip' => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('print_format_settings')
            ->where('document_type', 'ecommerce')
            ->delete();
    }
};
