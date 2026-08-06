<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('suggested_price', 12, 2)->nullable()->after('special_price');
        });

        Schema::table('product_children', function (Blueprint $table) {
            $table->decimal('suggested_price', 12, 2)->nullable()->after('special_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('suggested_price');
        });

        Schema::table('product_children', function (Blueprint $table) {
            $table->dropColumn('suggested_price');
        });
    }
};
