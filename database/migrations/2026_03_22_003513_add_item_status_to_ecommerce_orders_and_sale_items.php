<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add status field to ecommerce_orders for tracking order fulfillment
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->after('customer_notes');
            // pending, approved, rejected, partial (has unavailable items)
        });

        // Add item-level unavailability tracking to sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_unavailable')->default(false)->after('total');
            $table->string('unavailable_reason')->nullable()->after('is_unavailable');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_unavailable', 'unavailable_reason']);
        });
    }
};
