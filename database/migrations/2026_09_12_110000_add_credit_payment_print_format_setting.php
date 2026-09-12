<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('print_format_settings')->insertOrIgnore([
            'document_type' => 'credit_payment',
            'display_name'  => 'Comprobante de Abono / Crédito',
            'format'        => '80mm',
            'letter_options' => json_encode(\App\Models\PrintFormatSetting::DEFAULT_LETTER_OPTIONS),
            'show_logo_80mm' => true,
            'show_observations_80mm' => true,
            'open_cash_drawer_on_skip' => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('print_format_settings')
            ->where('document_type', 'credit_payment')
            ->delete();
    }
};
