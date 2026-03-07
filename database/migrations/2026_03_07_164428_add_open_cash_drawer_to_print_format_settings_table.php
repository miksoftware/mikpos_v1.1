<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->boolean('open_cash_drawer_on_skip')->default(false)->after('letter_options');
        });
    }

    public function down(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->dropColumn('open_cash_drawer_on_skip');
        });
    }
};
