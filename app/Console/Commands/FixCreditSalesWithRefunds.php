<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;

class FixCreditSalesWithRefunds extends Command
{
    protected $signature = 'fix:credit-sales {--dry-run : Show what would be done without making changes}';
    protected $description = 'Fix credit sales that have refunds/credit notes but still show as pending in credits';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $sales = Sale::with(['refunds', 'creditNotes'])
            ->where('payment_type', 'credit')
            ->where('status', 'completed')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->get();

        $this->info("Found {$sales->count()} credit sale(s) with pending/partial status...");

        $fixed = 0;

        foreach ($sales as $sale) {
            $totalRefunded = (float) $sale->refunds()->where('status', 'completed')->sum('total');
            $totalCredited = (float) $sale->creditNotes()->whereIn('status', ['pending', 'validated'])->sum('total');
            $totalReturned = $totalRefunded + $totalCredited;

            if ($totalReturned <= 0) {
                continue;
            }

            $newCreditAmount = max(0, (float) $sale->total - $totalReturned);
            $paidAmount = (float) $sale->paid_amount;

            if ($newCreditAmount <= 0) {
                $newPaymentStatus = 'paid';
            } elseif ($paidAmount >= $newCreditAmount) {
                $newPaymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $newPaymentStatus = 'partial';
            } else {
                $newPaymentStatus = 'pending';
            }

            $changed = $newCreditAmount != (float) $sale->credit_amount || $newPaymentStatus !== $sale->payment_status;

            if (!$changed) {
                continue;
            }

            $this->newLine();
            $this->info("Venta {$sale->invoice_number}:");
            $this->line("  Total: \${$sale->total} | Devuelto: \${$totalReturned} | Pagado: \${$paidAmount}");
            $this->line("  credit_amount: {$sale->credit_amount} → {$newCreditAmount}");
            $this->line("  payment_status: {$sale->payment_status} → {$newPaymentStatus}");

            if (!$dryRun) {
                $sale->update([
                    'credit_amount' => $newCreditAmount,
                    'payment_status' => $newPaymentStatus,
                ]);
            }

            $fixed++;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("[DRY RUN] Would fix {$fixed} credit sale(s)");
        } else {
            $this->info("Fixed {$fixed} credit sale(s)");
        }

        return 0;
    }
}
