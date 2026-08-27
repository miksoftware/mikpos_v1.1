<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->boolean('show_observations_80mm')->default(true)->after('show_logo_80mm');
        });
    }

    public function down(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->dropColumn('show_observations_80mm');
        });
    }
};
