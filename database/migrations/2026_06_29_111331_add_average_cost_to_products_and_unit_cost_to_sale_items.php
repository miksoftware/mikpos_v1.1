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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('average_cost', 12, 2)->after('purchase_price')->default(0);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->after('unit_price')->default(0);
        });

        // Initialize average_cost with the current purchase_price
        \Illuminate\Support\Facades\DB::statement('UPDATE products SET average_cost = purchase_price');
        
        // Initialize unit_cost for existing sale_items using the product's average_cost
        // If it's a child product, it multiplies by unit_quantity
        \Illuminate\Support\Facades\DB::statement('
            UPDATE sale_items si 
            LEFT JOIN product_children pc ON si.product_child_id = pc.id
            JOIN products p ON si.product_id = p.id 
            SET si.unit_cost = p.average_cost * COALESCE(pc.unit_quantity, 1)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('average_cost');
        });
    }
};
