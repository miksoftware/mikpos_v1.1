<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUtcDates extends Command
{
    protected $signature = 'fix:utc-dates 
        {--dry-run : Show what would be changed without applying}
        {--revert-overcorrected : Fix records where date correction moved them to wrong day}';
    protected $description = 'Fix dates stored in UTC to America/Bogota (subtract 5 hours)';

    private int $totalUpdated = 0;

    public function handle(): int
    {
        if ($this->option('revert-overcorrected')) {
            return $this->fixOvercorrected();
        }

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN - No changes will be applied');
        } else {
            if (!$this->confirm('⚠️  This will subtract 5 hours from all datetime columns in sales-related tables. Make sure APP_TIMEZONE=America/Bogota is set in .env BEFORE running this. Continue?')) {
                return 0;
            }
        }

        $tables = [
            'sales' => ['created_at', 'updated_at'],
            'sale_items' => ['created_at', 'updated_at'],
            'sale_payments' => ['created_at', 'updated_at'],
            'cash_reconciliations' => ['opened_at', 'closed_at', 'created_at', 'updated_at'],
            'cash_movements' => ['created_at', 'updated_at'],
            'credit_payments' => ['created_at', 'updated_at'],
            'inventory_movements' => ['created_at', 'updated_at'],
            'credit_notes' => ['created_at', 'updated_at'],
            'refunds' => ['created_at', 'updated_at'],
        ];

        foreach ($tables as $table => $columns) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->line("  ⏭  Table '{$table}' does not exist, skipping.");
                continue;
            }

            $count = DB::table($table)->count();
            if ($count === 0) {
                $this->line("  ⏭  Table '{$table}' is empty, skipping.");
                continue;
            }

            foreach ($columns as $column) {
                if (!DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }

                $affected = DB::table($table)->whereNotNull($column)->count();

                if ($isDryRun) {
                    $this->info("  Would update {$affected} rows in '{$table}.{$column}'");
                } else {
                    DB::table($table)
                        ->whereNotNull($column)
                        ->update([
                            $column => DB::raw("DATE_SUB({$column}, INTERVAL 5 HOUR)"),
                        ]);
                    $this->info("  ✅ Updated {$affected} rows in '{$table}.{$column}'");
                }

                $this->totalUpdated += $affected;
            }
        }

        $this->newLine();
        if ($isDryRun) {
            $this->warn("Total rows that would be affected: {$this->totalUpdated}");
        } else {
            $this->info("✅ Done. Total rows updated: {$this->totalUpdated}");
            $this->warn('Remember: Run this command ONLY ONCE. Running it again will subtract another 5 hours.');
        }

        return 0;
    }

    /**
     * Fix sales whose created_at was over-corrected and moved to the wrong day.
     * Uses the invoice_number date (FAC-YYYYMMDD-XXXX) as the source of truth.
     */
    private function fixOvercorrected(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN - No changes will be applied');
        }

        $this->info('Scanning for sales where created_at date does not match invoice_number date...');

        // Find sales where the date in created_at doesn't match the date in invoice_number
        $mismatched = DB::table('sales')
            ->whereRaw("DATE_FORMAT(created_at, '%Y%m%d') != SUBSTRING(invoice_number, 5, 8)")
            ->where('invoice_number', 'like', 'FAC-%')
            ->get();

        if ($mismatched->isEmpty()) {
            $this->info('✅ No mismatched sales found. Everything looks correct.');
            return 0;
        }

        $this->warn("Found {$mismatched->count()} sales with date mismatch.");

        foreach ($mismatched as $sale) {
            // Extract the correct date from invoice_number (FAC-YYYYMMDD-XXXX)
            $parts = explode('-', $sale->invoice_number);
            if (count($parts) !== 3) {
                continue;
            }

            $invoiceDate = $parts[1]; // YYYYMMDD
            $currentDate = date('Y-m-d H:i:s', strtotime($sale->created_at));
            $currentDateOnly = date('Ymd', strtotime($sale->created_at));

            // The time part is correct (already in Colombia time), just the date rolled back
            $timePart = date('H:i:s', strtotime($sale->created_at));
            $correctDate = substr($invoiceDate, 0, 4) . '-' . substr($invoiceDate, 4, 2) . '-' . substr($invoiceDate, 6, 2);
            $correctedDatetime = "{$correctDate} {$timePart}";

            if ($isDryRun) {
                $this->line("  #{$sale->id} {$sale->invoice_number}: {$currentDate} → {$correctedDatetime}");
            } else {
                // Update the sale
                DB::table('sales')->where('id', $sale->id)->update([
                    'created_at' => $correctedDatetime,
                    'updated_at' => $correctedDatetime,
                ]);

                // Update related sale_items
                DB::table('sale_items')->where('sale_id', $sale->id)->update([
                    'created_at' => $correctedDatetime,
                    'updated_at' => $correctedDatetime,
                ]);

                // Update related sale_payments
                DB::table('sale_payments')->where('sale_id', $sale->id)->update([
                    'created_at' => $correctedDatetime,
                    'updated_at' => $correctedDatetime,
                ]);

                $this->info("  ✅ #{$sale->id} {$sale->invoice_number}: {$currentDate} → {$correctedDatetime}");
            }

            $this->totalUpdated++;
        }

        $this->newLine();
        if ($isDryRun) {
            $this->warn("Total sales that would be fixed: {$this->totalUpdated}");
        } else {
            $this->info("✅ Fixed {$this->totalUpdated} sales.");
        }

        return 0;
    }
}
