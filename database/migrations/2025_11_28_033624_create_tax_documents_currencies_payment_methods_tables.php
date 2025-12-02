<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Documentos Tributarios
        Schema::create('tax_documents', function (Blueprint $table) {
            $table->id();
            $table->string('dian_code', 10)->unique();
            $table->string('description');
            $table->string('abbreviation', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tipos de Moneda
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique()->comment('ISO code like USD, COP');
            $table->string('symbol', 5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Medios de Pago
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('dian_code', 10)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('tax_documents');
    }
};
