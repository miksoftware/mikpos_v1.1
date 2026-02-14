<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUtcDates extends Command
{
    protected $signature = 'fix:utc-dates {--dry-run : Show what would be changed without applying}';
    protected $description = 'Fix dates stored in UTC to America/Bogota (subtract 5 hours)';

    private int $totalUpdated = 0;

    public function handle(): int
    {
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
}
