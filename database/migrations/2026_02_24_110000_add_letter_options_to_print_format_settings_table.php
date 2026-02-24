<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->json('letter_options')->nullable()->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('print_format_settings', function (Blueprint $table) {
            $table->dropColumn('letter_options');
        });
    }
};
