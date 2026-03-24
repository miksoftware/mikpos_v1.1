<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('ecommerce_enabled')->default(false)->after('show_in_pos');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('ecommerce_enabled');
        });
    }
};
