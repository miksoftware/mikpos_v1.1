<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('original_quantity', 12, 3)->nullable()->after('quantity');
            $table->string('quantity_change_reason')->nullable()->after('original_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['original_quantity', 'quantity_change_reason']);
        });
    }
};
