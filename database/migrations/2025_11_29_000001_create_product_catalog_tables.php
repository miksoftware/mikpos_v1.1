<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorías
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Subcategorías
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Marcas
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Unidades de Medida
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation', 10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Modelos (opcional)
        Schema::create('product_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Presentaciones (opcional)
        Schema::create('presentations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Colores (opcional)
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hex_code', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // IMEIs (opcional)
        Schema::create('imeis', function (Blueprint $table) {
            $table->id();
            $table->string('imei', 20)->unique();
            $table->string('imei2', 20)->nullable();
            $table->enum('status', ['available', 'sold', 'reserved'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imeis');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('presentations');
        Schema::dropIfExists('product_models');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');
    }
};
