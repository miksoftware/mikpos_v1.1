<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');           // Estante 1, Bodega 2, Pasillo A, etc.
            $table->string('code')->nullable(); // A-01, B-02 — código corto opcional
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });

        // Pivot: product ↔ location (a product can be in multiple locations)
        Schema::create('location_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3)->default(0); // how many units at this location
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'product_id']);
            $table->index('product_id');
        });

        // Location transfers log
        Schema::create('location_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_location_id')->constrained('locations');
            $table->foreignId('to_location_id')->constrained('locations');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
        });

        // Location transfer items
        Schema::create('location_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_transfer_items');
        Schema::dropIfExists('location_transfers');
        Schema::dropIfExists('location_products');
        Schema::dropIfExists('locations');
    }
};
