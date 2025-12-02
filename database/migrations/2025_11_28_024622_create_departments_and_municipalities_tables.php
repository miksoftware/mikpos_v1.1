<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('dian_code', 10)->nullable()->comment('Código DIAN del municipio');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('dian_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('departments');
    }
};
