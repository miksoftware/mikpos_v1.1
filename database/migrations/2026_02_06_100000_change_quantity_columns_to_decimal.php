<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changes quantity columns from integer to decimal to support products sold by weight (kg, lb, etc.)
     */
    public function up(): void
    {
        // Change sale_items.quantity to decimal
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
        });

        // Change inventory_movements columns to decimal
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
            $table->decimal('stock_before', 12, 3)->change();
            $table->decimal('stock_after', 12, 3)->change();
        });

        // Change products stock columns to decimal
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('min_stock', 12, 3)->default(0)->change();
            $table->decimal('max_stock', 12, 3)->nullable()->change();
            $table->decimal('current_stock', 12, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert sale_items.quantity to integer
        Schema::table('sale_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });

        // Revert inventory_movements columns to integer
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('stock_before')->change();
            $table->integer('stock_after')->change();
        });

        // Revert products stock columns to integer
        Schema::table('products', function (Blueprint $table) {
            $table->integer('min_stock')->default(0)->change();
            $table->integer('max_stock')->nullable()->change();
            $table->integer('current_stock')->default(0)->change();
        });
    }
};
