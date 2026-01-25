<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add commission fields to products table
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_commission')->default(false)->after('is_active');
            $table->enum('commission_type', ['percentage', 'fixed'])->nullable()->after('has_commission');
            $table->decimal('commission_value', 10, 2)->nullable()->after('commission_type');
        });

        // Add commission fields to product_children table
        Schema::table('product_children', function (Blueprint $table) {
            $table->boolean('has_commission')->default(false)->after('is_active');
            $table->enum('commission_type', ['percentage', 'fixed'])->nullable()->after('has_commission');
            $table->decimal('commission_value', 10, 2)->nullable()->after('commission_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['has_commission', 'commission_type', 'commission_value']);
        });

        Schema::table('product_children', function (Blueprint $table) {
            $table->dropColumn(['has_commission', 'commission_type', 'commission_value']);
        });
    }
};
