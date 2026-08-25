<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('enable_costo_fe')->default(false)->after('print_qr');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('unit_cost_fe', 15, 2)->nullable()->after('unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('enable_costo_fe');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost_fe');
        });
    }
};
