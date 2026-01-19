<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal de combos
        Schema::create('combos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('combo_price', 12, 2); // Precio del combo
            $table->decimal('original_price', 12, 2)->default(0); // Suma de precios originales (calculado)
            
            // Tipo de límite: 'time' (por tiempo), 'quantity' (por cantidad), 'both' (ambos), 'none' (sin límite)
            $table->enum('limit_type', ['time', 'quantity', 'both', 'none'])->default('none');
            
            // Límite por tiempo
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            
            // Límite por cantidad
            $table->integer('max_sales')->nullable(); // Cantidad máxima de ventas
            $table->integer('current_sales')->default(0); // Ventas actuales
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla pivote para productos del combo
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained()->cascadeOnDelete();
            
            // Puede ser producto padre o hijo
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_child_id')->nullable()->constrained()->nullOnDelete();
            
            $table->integer('quantity')->default(1); // Cantidad de este producto en el combo
            $table->decimal('unit_price', 12, 2); // Precio unitario al momento de agregar
            
            $table->timestamps();
            
            // Al menos uno debe estar presente
            $table->index(['combo_id', 'product_id']);
            $table->index(['combo_id', 'product_child_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
        Schema::dropIfExists('combos');
    }
};
