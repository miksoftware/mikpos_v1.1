<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows customers and suppliers to be scoped or copied across branches
     * by replacing global unique constraints with branch-scoped indexes.
     */
    public function up(): void
    {
        // 1. Customers: Drop global unique constraint on document_number
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                try {
                    $table->dropUnique('customers_document_number_unique');
                } catch (\Throwable $e) {
                    // Unique index might not exist or have a different name
                }
                try {
                    $table->index('document_number', 'customers_document_number_index');
                } catch (\Throwable $e) {
                    // Index may already exist
                }
                try {
                    $table->index(['branch_id', 'document_number'], 'customers_branch_document_index');
                } catch (\Throwable $e) {
                    // Index may already exist
                }
            });
        }

        // 2. Suppliers: Add branch_id and replace global unique constraint on document_number
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
                    $table->index('branch_id', 'suppliers_branch_id_index');
                }
                try {
                    $table->dropUnique('suppliers_document_number_unique');
                } catch (\Throwable $e) {
                    // Unique index might not exist
                }
                try {
                    $table->index('document_number', 'suppliers_document_number_index');
                } catch (\Throwable $e) {
                    // Index may already exist
                }
                try {
                    $table->index(['branch_id', 'document_number'], 'suppliers_branch_document_index');
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
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                try {
                    $table->dropIndex('suppliers_branch_document_index');
                } catch (\Throwable $e) {}
                try {
                    $table->dropIndex('suppliers_document_number_index');
                } catch (\Throwable $e) {}
                if (Schema::hasColumn('suppliers', 'branch_id')) {
                    $table->dropForeign(['branch_id']);
                    $table->dropIndex('suppliers_branch_id_index');
                    $table->dropColumn('branch_id');
                }
                try {
                    $table->unique('document_number', 'suppliers_document_number_unique');
                } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                try {
                    $table->dropIndex('customers_branch_document_index');
                } catch (\Throwable $e) {}
                try {
                    $table->dropIndex('customers_document_number_index');
                } catch (\Throwable $e) {}
                try {
                    $table->unique('document_number', 'customers_document_number_unique');
                } catch (\Throwable $e) {}
            });
        }
    }
};
