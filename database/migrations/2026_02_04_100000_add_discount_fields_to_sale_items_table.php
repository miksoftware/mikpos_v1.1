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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount_type_value', 10, 2)->nullable()->after('subtotal'); // The value entered (percentage or fixed amount)
            $table->string('discount_type', 20)->nullable()->after('discount_type_value'); // 'percentage' or 'fixed'
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_type'); // Calculated discount amount
            $table->string('discount_reason')->nullable()->after('discount_amount'); // Reason for discount
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type_value', 'discount_type', 'discount_amount', 'discount_reason']);
        });
    }
};
