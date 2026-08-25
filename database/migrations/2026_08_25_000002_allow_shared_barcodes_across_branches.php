<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows identical physical barcodes to exist across multiple branches
     * by replacing global unique constraints with standard lookup indexes.
     */
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                try {
                    $table->dropUnique('products_barcode_unique');
                } catch (\Throwable $e) {
                    // Unique index might not exist or have different name
                }
                try {
                    $table->index('barcode', 'products_barcode_index');
                } catch (\Throwable $e) {
                    // Index may already exist
                }
            });
        }

        if (Schema::hasTable('product_children')) {
            Schema::table('product_children', function (Blueprint $table) {
                try {
                    $table->dropUnique('product_children_barcode_unique');
                } catch (\Throwable $e) {
                    // Unique index might not exist or have different name
                }
                try {
                    $table->index('barcode', 'product_children_barcode_index');
                } catch (\Throwable $e) {
                    // Index may already exist
                }
            });
        }

        if (Schema::hasTable('product_barcodes')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                try {
                    $table->dropUnique('product_barcodes_barcode_unique');
                } catch (\Throwable $e) {
                    // Unique index might not exist or have different name
                }
                try {
                    $table->index('barcode', 'product_barcodes_barcode_index');
                } catch (\Throwable $e) {
                    // Index may already exist
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                try {
                    $table->dropIndex('products_barcode_index');
                } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('product_children')) {
            Schema::table('product_children', function (Blueprint $table) {
                try {
                    $table->dropIndex('product_children_barcode_index');
                } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('product_barcodes')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                try {
                    $table->dropIndex('product_barcodes_barcode_index');
                } catch (\Throwable $e) {}
            });
        }
    }
};
