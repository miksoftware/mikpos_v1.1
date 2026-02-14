<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUtcDates extends Command
{
    protected $signature = 'fix:utc-dates
        {--dry-run : Show what would be changed without applying}
        {--cutoff= : Only subtract hours from records created before this datetime (Y-m-d H:i:s in current DB time)}';
    protected $description = 'Fix all dates from UTC to America/Bogota and correct day-shifted sales';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $cutoff = $this->option('cutoff');

        if ($isDryRun) {
            $this->warn('DRY RUN - No changes will be applied');
        } else {
            $this->warn('This command will:');
            $this->line('  1. Subtract 5 hours from datetime columns' . ($cutoff ? " (only records before {$cutoff})" : ''));
            $this->line('  2. Fix sales whose date shifted to wrong day');
            $this->newLine();
            $this->warn('Ensure APP_TIMEZONE=America/Bogota is in .env.');
            $this->warn('Run ONLY ONCE per environment.');
            if (!$this->confirm('Continue?')) {
                return 0;
            }
        }

        $this->step1SubtractHours($isDryRun, $cutoff);
        $this->step2FixDayShiftedSales($isDryRun);

        $this->newLine();
        $this->info('All done.');
        return 0;
    }

    private function step1SubtractHours(bool $isDryRun, ?string $cutoff): void
    {
        $this->newLine();
        $this->info('== STEP 1: Subtract 5 hours from datetime columns ==');
        if ($cutoff) {
            $this->line("  Cutoff: only records with created_at < {$cutoff}");
        }

        $tables = [
            'sales' => ['created_at', 'updated_at'],
            'sale_items' => ['created_at', 'updated_at'],
            'sale_payments' => ['created_at', 'updated_at'],
            'sale_reprints' => ['created_at', 'updated_at'],
            'cash_reconciliations' => ['opened_at', 'closed_at', 'created_at', 'updated_at'],
            'cash_movements' => ['created_at', 'updated_at'],
            'credit_payments' => ['created_at', 'updated_at'],
            'credit_notes' => ['created_at', 'updated_at'],
            'credit_note_items' => ['created_at', 'updated_at'],
            'refunds' => ['created_at', 'updated_at'],
            'refund_items' => ['created_at', 'updated_at'],
            'inventory_movements' => ['created_at', 'updated_at'],
            'purchases' => ['created_at', 'updated_at'],
            'purchase_items' => ['created_at', 'updated_at'],
            'activity_logs' => ['created_at', 'updated_at'],
        ];

        $count = 0;
        foreach ($tables as $table => $columns) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            if (DB::table($table)->count() === 0) {
                continue;
            }
            foreach ($columns as $column) {
                if (!DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }

                $query = DB::table($table)->whereNotNull($column);

                // If cutoff provided, only update records before that time
                if ($cutoff) {
                    // Use created_at as reference for the cutoff filter
                    if (DB::getSchemaBuilder()->hasColumn($table, 'created_at')) {
                        $query->where('created_at', '<', $cutoff);
                    }
                }

                $affected = $query->count();
                if ($affected === 0) {
                    continue;
                }

                if (!$isDryRun) {
                    // Re-build query for update
                    $updateQuery = DB::table($table)->whereNotNull($column);
                    if ($cutoff && DB::getSchemaBuilder()->hasColumn($table, 'created_at')) {
                        $updateQuery->where('created_at', '<', $cutoff);
                    }
                    $updateQuery->update([$column => DB::raw("DATE_SUB({$column}, INTERVAL 5 HOUR)")]);
                }

                $label = $isDryRun ? 'would update' : 'updated';
                $this->line("  {$table}.{$column}: {$label} {$affected} rows");
                $count += $affected;
            }
        }
        $this->info("Step 1 complete: {$count} values processed.");
    }

    private function step2FixDayShiftedSales(bool $isDryRun): void
    {
        $this->newLine();
        $this->info('== STEP 2: Fix sales whose date shifted to wrong day ==');

        $today = now()->format('Ymd');

        // Find sales where created_at date != invoice_number date
        $mismatched = DB::table('sales')
            ->where('invoice_number', 'like', 'FAC-%')
            ->whereRaw("DATE_FORMAT(created_at, '%Y%m%d') != SUBSTRING(invoice_number, 5, 8)")
            ->get();

        if ($mismatched->isEmpty()) {
            $this->info('  No mismatched sales found. All good.');
            return;
        }

        $this->warn("  Found {$mismatched->count()} sales with date mismatch.");
        $fixed = 0;
        $skipped = 0;

        foreach ($mismatched as $sale) {
            $parts = explode('-', $sale->invoice_number);
            if (count($parts) !== 3) {
                continue;
            }

            $invoiceDateStr = $parts[1]; // YYYYMMDD

            // Skip if invoice date is in the future (those were created after timezone fix)
            if ($invoiceDateStr > $today) {
                if ($isDryRun) {
                    $this->line("  SKIP #{$sale->id} {$sale->invoice_number}: invoice date {$invoiceDateStr} is future, already correct");
                }
                $skipped++;
                continue;
            }

            $timePart = date('H:i:s', strtotime($sale->created_at));
            $correctDate = substr($invoiceDateStr, 0, 4) . '-'
                . substr($invoiceDateStr, 4, 2) . '-'
                . substr($invoiceDateStr, 6, 2);
            $corrected = "{$correctDate} {$timePart}";

            if ($isDryRun) {
                $this->line("  #{$sale->id} {$sale->invoice_number}: {$sale->created_at} -> {$corrected}");
            } else {
                DB::table('sales')->where('id', $sale->id)->update([
                    'created_at' => $corrected,
                    'updated_at' => $corrected,
                ]);
                DB::table('sale_items')->where('sale_id', $sale->id)->update([
                    'created_at' => $corrected,
                    'updated_at' => $corrected,
                ]);
                DB::table('sale_payments')->where('sale_id', $sale->id)->update([
                    'created_at' => $corrected,
                    'updated_at' => $corrected,
                ]);
                $this->line("  #{$sale->id} {$sale->invoice_number}: fixed -> {$corrected}");
            }
            $fixed++;
        }

        $label = $isDryRun ? 'would be' : '';
        $this->info("Step 2 complete: {$fixed} sales {$label} corrected, {$skipped} skipped (future/already correct).");
    }
}
