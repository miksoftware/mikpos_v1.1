<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen cost columns from decimal(12,2) to decimal(15,2) to avoid
     * "Numeric value out of range" errors on large production/purchase orders.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 2)->nullable()->change();
            $table->decimal('total_cost', 15, 2)->nullable()->change();
        });

        Schema::table('production_order_details', function (Blueprint $table) {
            $table->decimal('unit_cost_at_time', 15, 2)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)->default(0)->change();
            $table->decimal('average_cost', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->nullable()->change();
            $table->decimal('total_cost', 12, 2)->nullable()->change();
        });

        Schema::table('production_order_details', function (Blueprint $table) {
            $table->decimal('unit_cost_at_time', 12, 2)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->default(0)->change();
            $table->decimal('average_cost', 12, 2)->default(0)->change();
        });
    }
};
