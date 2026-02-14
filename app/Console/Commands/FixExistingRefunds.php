<?php

namespace App\Console\Commands;

use App\Models\CashMovement;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Refund;
use Illuminate\Console\Command;

class FixExistingRefunds extends Command
{
    protected $signature = 'fix:refunds {--dry-run : Show what would be done without making changes}';
    protected $description = 'Fix existing refunds that did not return inventory or register cash movements';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $refunds = Refund::with(['items', 'sale', 'cashReconciliation'])
            ->where('status', 'completed')
            ->get();

        $this->info("Found {$refunds->count()} completed refund(s) to check...");

        $fixedInventory = 0;
        $fixedCash = 0;

        foreach ($refunds as $refund) {
            $this->newLine();
            $saleNumber = $refund->sale->invoice_number ?? 'N/A';
            $this->info("Refund #{$refund->number} (Sale: {$saleNumber})");

            // Check if inventory movements exist for this refund
            $hasInventoryMovements = InventoryMovement::where('reference_type', Refund::class)
                ->where('reference_id', $refund->id)
                ->exists();

            if (!$hasInventoryMovements) {
                $this->warn("  → Missing inventory movements. Fixing...");

                foreach ($refund->items as $item) {
                    if (!$item->product_id) {
                        $this->line("    Skipping service item: {$item->product_name}");
                        continue;
                    }

                    $product = Product::find($item->product_id);
                    if (!$product) {
                        $this->warn("    Product #{$item->product_id} not found, skipping");
                        continue;
                    }

                    $quantity = (float) $item->quantity;
                    $this->line("    {$item->product_name}: +{$quantity} units back to stock");

                    if (!$dryRun) {
                        InventoryMovement::createMovement(
                            'refund',
                            $product,
                            'in',
                            $quantity,
                            (float) $item->unit_price,
                            "Devolución {$refund->number} - Venta {$saleNumber} (fix)",
                            $refund,
                            $refund->branch_id
                        );

                        $product->increment('current_stock', $quantity);
                    }

                    $fixedInventory++;
                }
            } else {
                $this->line("  ✓ Inventory movements already exist");
            }

            // Check if cash movement exists for this refund
            $hasCashMovement = false;
            if ($refund->cash_reconciliation_id) {
                $hasCashMovement = CashMovement::where('cash_reconciliation_id', $refund->cash_reconciliation_id)
                    ->where('concept', 'like', "%{$refund->number}%")
                    ->exists();
            }

            if (!$hasCashMovement && $refund->cash_reconciliation_id) {
                $this->warn("  → Missing cash movement. Fixing...");
                $this->line("    Egreso: \${$refund->total} in reconciliation #{$refund->cash_reconciliation_id}");

                if (!$dryRun) {
                    CashMovement::create([
                        'cash_reconciliation_id' => $refund->cash_reconciliation_id,
                        'type' => 'expense',
                        'amount' => $refund->total,
                        'concept' => "Devolución {$refund->number}",
                        'notes' => "Movimiento creado por fix:refunds",
                        'user_id' => $refund->user_id,
                    ]);
                }

                $fixedCash++;
            } elseif (!$refund->cash_reconciliation_id) {
                $this->warn("  → No cash reconciliation linked, cannot create cash movement");
            } else {
                $this->line("  ✓ Cash movement already exists");
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("[DRY RUN] Would fix {$fixedInventory} inventory item(s) and {$fixedCash} cash movement(s)");
        } else {
            $this->info("✅ Fixed {$fixedInventory} inventory item(s) and {$fixedCash} cash movement(s)");
        }

        return 0;
    }
}
