<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make product_id nullable and add combo_id to refund_items
        Schema::table('refund_items', function (Blueprint $table) {
            $table->foreignId('combo_id')->nullable()->after('sale_item_id')->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->change();
        });

        // Make product_id nullable and add combo_id to credit_note_items
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->foreignId('combo_id')->nullable()->after('sale_item_id')->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('refund_items', function (Blueprint $table) {
            $table->dropForeign(['combo_id']);
            $table->dropColumn('combo_id');
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropForeign(['combo_id']);
            $table->dropColumn('combo_id');
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
