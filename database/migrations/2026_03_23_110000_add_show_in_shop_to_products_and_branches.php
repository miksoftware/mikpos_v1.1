<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_in_shop')->default(true)->after('manages_inventory');
        });

        Schema::table('product_children', function (Blueprint $table) {
            $table->boolean('show_in_shop')->default(true)->after('is_active');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('show_stock_in_shop')->default(false)->after('ecommerce_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('show_in_shop');
        });

        Schema::table('product_children', function (Blueprint $table) {
            $table->dropColumn('show_in_shop');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('show_stock_in_shop');
        });
    }
};
