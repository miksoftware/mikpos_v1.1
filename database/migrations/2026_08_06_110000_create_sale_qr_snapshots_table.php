<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_qr_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->string('qr_token', 64)->unique();

            // Snapshot permanente de la factura (JSON) - nunca se actualiza
            $table->json('sale_snapshot');

            // Snapshot permanente de documentos de importación agrupados por código único
            // Formato: [{import_code, product_names[], file_path}]
            $table->json('import_declarations')->nullable();

            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->index('qr_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_qr_snapshots');
    }
};
