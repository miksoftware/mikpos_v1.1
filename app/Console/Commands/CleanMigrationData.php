<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanMigrationData extends Command
{
    protected $signature = 'migration:clean {--force : Skip confirmation}';
    protected $description = 'Clean all business data for a fresh migration test. Keeps users, branches, roles, permissions, config.';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will DELETE all products, sales, purchases, customers, suppliers, etc. Continue?')) {
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            // Sales & related
            'sale_reprints',
            'sale_payments',
            'sale_items',
            'sales',
            // Refunds & credit notes
            'refund_items',
            'refunds',
            'credit_note_items',
            'credit_notes',
            'credit_payments',
            // Purchases
            'purchase_items',
            'purchases',
            // Inventory
            'inventory_movements',
            // Cash
            'cash_movements',
            'cash_reconciliation_edits',
            'cash_reconciliations',
            'cash_registers',
            // Combos
            'combo_items',
            'combos',
            // Products
            'product_barcodes',
            'product_children',
            'products',
            'imeis',
            // Services
            'services',
            // Customers & Suppliers
            'customers',
            'suppliers',
            // Catalog
            'subcategories',
            'categories',
            'presentations',
            'colors',
            'product_models',
            'brands',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info("✓ Truncated: {$table}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info('✅ All business data cleaned. Users, branches, roles, permissions, taxes, units, and config preserved.');

        return 0;
    }
}
