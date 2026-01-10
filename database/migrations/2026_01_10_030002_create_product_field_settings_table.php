<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_field_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('field_name', 50);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Índice único para evitar duplicados de campo por sucursal
            $table->unique(['branch_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_field_settings');
    }
};
