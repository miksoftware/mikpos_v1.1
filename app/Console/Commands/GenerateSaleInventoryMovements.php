<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SystemDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSaleInventoryMovements extends Command
{
    protected $signature = 'inventory:generate-sale-movements {--force : Run without confirmation}';
    protected $description = 'Generate inventory movements for historical sales that do not have them';

    public function handle(): int
    {
        $this->info('Checking for sales without inventory movements...');

        // Get the sale system document
        $saleDocument = SystemDocument::where('code', 'sale')->first();
        if (!$saleDocument) {
            $this->error('System document "sale" not found. Please run seeders first.');
            return 1;
        }

        // Find completed sales that don't have inventory movements
        $salesWithoutMovements = Sale::where('status', 'completed')
            ->whereDoesntHave('inventoryMovements')
            ->with(['items.product', 'branch'])
            ->orderBy('created_at')
            ->get();

        if ($salesWithoutMovements->isEmpty()) {
            $this->info('All sales already have inventory movements. Nothing to do.');
            return 0;
        }

        $this->info("Found {$salesWithoutMovements->count()} sales without inventory movements.");

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to generate inventory movements for these sales?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $bar = $this->output->createProgressBar($salesWithoutMovements->count());
        $bar->start();

        $movementsCreated = 0;
        $errors = [];

        foreach ($salesWithoutMovements as $sale) {
            try {
                DB::beginTransaction();

                foreach ($sale->items as $item) {
                    // Skip services (no product_id)
                    if (!$item->product_id) {
                        continue;
                    }

                    $product = $item->product;
                    if (!$product) {
                        continue;
                    }

                    // Calculate what the stock was before this sale
                    // We need to work backwards from current stock
                    $stockAfter = $this->calculateStockAtTime($product, $sale->created_at);
                    $stockBefore = $stockAfter + $item->quantity;

                    // Create the inventory movement
                    InventoryMovement::create([
                        'system_document_id' => $saleDocument->id,
                        'document_number' => $saleDocument->generateNextNumber(),
                        'product_id' => $product->id,
                        'branch_id' => $sale->branch_id,
                        'user_id' => $sale->user_id,
                        'movement_type' => 'out',
                        'quantity' => $item->quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'unit_cost' => $item->unit_price,
                        'total_cost' => $item->unit_price * $item->quantity,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => "Venta #{$sale->invoice_number}",
                        'movement_date' => $sale->created_at->toDateString(),
                        'created_at' => $sale->created_at,
                        'updated_at' => $sale->created_at,
                    ]);

                    $movementsCreated++;
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Sale #{$sale->invoice_number}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Created {$movementsCreated} inventory movements.");

        if (!empty($errors)) {
            $this->warn('Some errors occurred:');
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->warn('  ... and ' . (count($errors) - 10) . ' more errors.');
            }
        }

        return 0;
    }

    /**
     * Calculate what the stock was at a given time.
     * This is an approximation based on current stock and subsequent movements.
     */
    private function calculateStockAtTime(Product $product, $dateTime): int
    {
        // Get all movements after this date for this product
        $subsequentMovements = InventoryMovement::where('product_id', $product->id)
            ->where('created_at', '>', $dateTime)
            ->get();

        $currentStock = $product->current_stock;

        // Reverse the effect of subsequent movements
        foreach ($subsequentMovements as $movement) {
            if ($movement->movement_type === 'in') {
                $currentStock -= $movement->quantity;
            } else {
                $currentStock += $movement->quantity;
            }
        }

        return $currentStock;
    }
}
