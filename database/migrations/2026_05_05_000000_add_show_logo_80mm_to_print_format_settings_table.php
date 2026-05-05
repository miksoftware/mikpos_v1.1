<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->boolean('show_logo_80mm')->default(false)->after('open_cash_drawer_on_skip');
        });
    }

    public function down(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->dropColumn('show_logo_80mm');
        });
    }
};
