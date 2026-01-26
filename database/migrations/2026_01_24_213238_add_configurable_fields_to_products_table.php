<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('presentation_id')->nullable()->after('tax_id')->constrained()->nullOnDelete();
            $table->foreignId('color_id')->nullable()->after('presentation_id')->constrained()->nullOnDelete();
            $table->foreignId('product_model_id')->nullable()->after('color_id')->constrained()->nullOnDelete();
            $table->string('size', 50)->nullable()->after('product_model_id');
            $table->decimal('weight', 10, 3)->nullable()->after('size');
            $table->string('imei', 17)->nullable()->after('weight');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['presentation_id']);
            $table->dropForeign(['color_id']);
            $table->dropForeign(['product_model_id']);
            $table->dropColumn(['presentation_id', 'color_id', 'product_model_id', 'size', 'weight', 'imei']);
        });
    }
};
