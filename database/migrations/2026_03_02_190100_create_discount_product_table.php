<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['discount_id', 'product_id']);
        });

        // Migrate any existing 'product' scope discounts to the pivot table
        // and update scope to 'products'
        $discounts = \Illuminate\Support\Facades\DB::table('discounts')
            ->where('scope', 'product')
            ->whereNotNull('scope_id')
            ->get();

        foreach ($discounts as $discount) {
            \Illuminate\Support\Facades\DB::table('discount_product')->insert([
                'discount_id' => $discount->id,
                'product_id' => $discount->scope_id,
            ]);
        }

        \Illuminate\Support\Facades\DB::table('discounts')
            ->where('scope', 'product')
            ->update(['scope' => 'products', 'scope_id' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_product');
    }
};
