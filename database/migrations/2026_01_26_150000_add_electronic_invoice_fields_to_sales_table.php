<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Electronic invoice fields
            $table->string('cufe')->nullable()->after('invoice_number'); // DIAN unique code
            $table->string('qr_code')->nullable()->after('cufe'); // QR code URL
            $table->string('dian_number')->nullable()->after('qr_code'); // DIAN invoice number (prefix + number)
            $table->timestamp('dian_validated_at')->nullable()->after('dian_number');
            $table->json('dian_response')->nullable()->after('dian_validated_at'); // Full DIAN response
            $table->boolean('is_electronic')->default(false)->after('dian_response');
            $table->string('reference_code')->nullable()->after('is_electronic'); // Unique reference for Factus
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'cufe',
                'qr_code',
                'dian_number',
                'dian_validated_at',
                'dian_response',
                'is_electronic',
                'reference_code',
            ]);
        });
    }
};
