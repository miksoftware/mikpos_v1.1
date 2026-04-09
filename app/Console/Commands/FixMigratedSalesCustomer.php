<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Console\Command;

class FixMigratedSalesCustomer extends Command
{
    protected $signature = 'fix:migrated-sales-customer {--branch= : Branch ID to fix} {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Assign default customer (Consumidor Final) to migrated sales without customer';

    public function handle(): int
    {
        $branchId = $this->option('branch');
        $dryRun = $this->option('dry-run');

        $query = Sale::whereNull('customer_id');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No hay ventas sin cliente asignado.');
            return 0;
        }

        $this->info("Ventas sin cliente: {$count}");

        // Get branches with sales without customer
        $branchIds = Sale::whereNull('customer_id')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->distinct()
            ->pluck('branch_id');

        $totalUpdated = 0;

        foreach ($branchIds as $bid) {
            $defaultCustomer = Customer::where('is_default', true)
                ->where('branch_id', $bid)
                ->first();

            if (!$defaultCustomer) {
                $this->warn("Sucursal #{$bid}: No tiene cliente por defecto (Consumidor Final). Omitida.");
                continue;
            }

            $branchCount = Sale::whereNull('customer_id')
                ->where('branch_id', $bid)
                ->count();

            if ($dryRun) {
                $this->info("Sucursal #{$bid}: Se asignarían {$branchCount} ventas a '{$defaultCustomer->full_name}' (ID: {$defaultCustomer->id})");
            } else {
                Sale::whereNull('customer_id')
                    ->where('branch_id', $bid)
                    ->update(['customer_id' => $defaultCustomer->id]);

                $this->info("Sucursal #{$bid}: {$branchCount} ventas asignadas a '{$defaultCustomer->full_name}' (ID: {$defaultCustomer->id})");
            }

            $totalUpdated += $branchCount;
        }

        $action = $dryRun ? 'se actualizarían' : 'actualizadas';
        $this->info("Total: {$totalUpdated} ventas {$action}.");

        return 0;
    }
}
