<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('print_format_settings')->insertOrIgnore([
            'document_type' => 'refund',
            'display_name'  => 'Devoluciones',
            'format'        => '80mm',
            'letter_options' => json_encode(\App\Models\PrintFormatSetting::DEFAULT_LETTER_OPTIONS),
            'show_logo_80mm' => false,
            'open_cash_drawer_on_skip' => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('print_format_settings')
            ->where('document_type', 'refund')
            ->delete();
    }
};
