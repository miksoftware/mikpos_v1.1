<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('purchase_price', 12, 2)->default(0)->after('image');
            $table->decimal('sale_price', 12, 2)->default(0)->after('purchase_price');
            $table->boolean('price_includes_tax')->default(false)->after('sale_price');
            $table->integer('min_stock')->default(0)->after('price_includes_tax');
            $table->integer('max_stock')->nullable()->after('min_stock');
            $table->integer('current_stock')->default(0)->after('max_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_price',
                'sale_price',
                'price_includes_tax',
                'min_stock',
                'max_stock',
                'current_stock',
            ]);
        });
    }
};
