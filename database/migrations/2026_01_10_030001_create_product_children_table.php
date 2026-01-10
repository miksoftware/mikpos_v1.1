<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 50)->unique()->nullable();
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('name');
            $table->foreignId('presentation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('color_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('size', 50)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->boolean('price_includes_tax')->default(false);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->integer('current_stock')->default(0);
            $table->string('image')->nullable();
            $table->string('imei', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices adicionales para búsqueda
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_children');
    }
};
