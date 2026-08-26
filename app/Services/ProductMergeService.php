<?php

namespace App\Services;

use App\Models\ComboItem;
use App\Models\CreditNoteItem;
use App\Models\InventoryMovement;
use App\Models\LocationTransferItem;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductChild;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderItem;
use App\Models\PurchaseItem;
use App\Models\QuoteItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RefundItem;
use App\Models\SaleItem;
use App\Models\SystemDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ProductMergeService
{
    /**
     * Validate whether two products can be merged.
     *
     * @param Product $targetProduct The product to keep
     * @param Product $sourceProduct The product to merge and remove
     * @return array Validation info including combined stock
     * @throws InvalidArgumentException
     */
    public function validateMerge(Product $targetProduct, Product $sourceProduct): array
    {
        if ($targetProduct->id === $sourceProduct->id) {
            throw new InvalidArgumentException('No se puede unificar un producto consigo mismo.');
        }

        if ($targetProduct->branch_id && $sourceProduct->branch_id && $targetProduct->branch_id !== $sourceProduct->branch_id) {
            throw new InvalidArgumentException('Los productos seleccionados pertenecen a sucursales diferentes.');
        }

        $stockTarget = (float) $targetProduct->current_stock;
        $stockSource = (float) $sourceProduct->current_stock;
        $combinedStock = $stockTarget + $stockSource;

        if ($combinedStock < 0) {
            throw new InvalidArgumentException(
                "No se puede unificar: el stock resultante quedaría en NEGATIVO ({$combinedStock} unidades). Debe ajustar o comprar existencias antes de unificar."
            );
        }

        return [
            'stock_target' => $stockTarget,
            'stock_source' => $stockSource,
            'combined_stock' => $combinedStock,
            'can_merge' => true,
        ];
    }

    /**
     * Execute the product merge inside a database transaction.
     *
     * @param Product $targetProduct The product to keep
     * @param Product $sourceProduct The product to merge and delete
     * @param int|null $userId User performing the action
     * @return array Merge summary results
     * @throws \Throwable
     */
    public function merge(Product $targetProduct, Product $sourceProduct, ?int $userId = null): array
    {
        // 1. Validate
        $validation = $this->validateMerge($targetProduct, $sourceProduct);
        $stockTarget = $validation['stock_target'];
        $stockSource = $validation['stock_source'];
        $combinedStock = $validation['combined_stock'];

        $sourceId = $sourceProduct->id;
        $targetId = $targetProduct->id;
        $sourceName = $sourceProduct->name;
        $sourceSku = $sourceProduct->sku;
        $sourceBarcode = $sourceProduct->barcode;

        return DB::transaction(function () use (
            $targetProduct,
            $sourceProduct,
            $sourceId,
            $targetId,
            $sourceName,
            $sourceSku,
            $sourceBarcode,
            $stockTarget,
            $stockSource,
            $combinedStock,
            $userId
        ) {
            $oldTargetValues = $targetProduct->toArray();

            // 2. Reassign Sales & Purchases items
            $salesCount = SaleItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);
            $purchasesCount = PurchaseItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);

            // 3. Reassign Kardex / Inventory Movements
            $movementsCount = InventoryMovement::where('product_id', $sourceId)->update(['product_id' => $targetId]);

            // 4. Reassign Returns and Credits
            CreditNoteItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);
            RefundItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);

            // 5. Reassign Quotes & Combos
            QuoteItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);
            ComboItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);

            // 6. Reassign Production & Recipes
            Recipe::where('product_id', $sourceId)->update(['product_id' => $targetId]);
            RecipeIngredient::where('product_id', $sourceId)->update(['product_id' => $targetId]);
            if (Schema::hasColumn('production_orders', 'product_id')) {
                DB::table('production_orders')->where('product_id', $sourceId)->update(['product_id' => $targetId]);
            }
            ProductionOrderItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);
            ProductionOrderDetail::where('product_id', $sourceId)->update(['product_id' => $targetId]);

            // 7. Reassign Location Transfers
            LocationTransferItem::where('product_id', $sourceId)->update(['product_id' => $targetId]);

            // 8. Consolidate Location Products (location_products pivot)
            $sourceLocations = DB::table('location_products')->where('product_id', $sourceId)->get();
            foreach ($sourceLocations as $loc) {
                $targetLoc = DB::table('location_products')
                    ->where('product_id', $targetId)
                    ->where('location_id', $loc->location_id)
                    ->first();

                if ($targetLoc) {
                    DB::table('location_products')
                        ->where('product_id', $targetId)
                        ->where('location_id', $loc->location_id)
                        ->update([
                            'quantity' => (float) $targetLoc->quantity + (float) $loc->quantity,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('location_products')->insert([
                        'product_id' => $targetId,
                        'location_id' => $loc->location_id,
                        'quantity' => $loc->quantity,
                        'notes' => $loc->notes,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::table('location_products')->where('product_id', $sourceId)->delete();

            // 9. Consolidate Discounts (discount_product pivot)
            $sourceDiscounts = DB::table('discount_product')->where('product_id', $sourceId)->pluck('discount_id');
            foreach ($sourceDiscounts as $discountId) {
                $exists = DB::table('discount_product')
                    ->where('product_id', $targetId)
                    ->where('discount_id', $discountId)
                    ->exists();

                if (!$exists) {
                    DB::table('discount_product')->insert([
                        'product_id' => $targetId,
                        'discount_id' => $discountId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::table('discount_product')->where('product_id', $sourceId)->delete();

            // 10. Consolidate Barcodes
            $sourceBarcodes = ProductBarcode::where('product_id', $sourceId)->whereNull('product_child_id')->get();
            foreach ($sourceBarcodes as $barcodeRecord) {
                $barcodeStr = trim($barcodeRecord->barcode);
                if (empty($barcodeStr)) {
                    $barcodeRecord->delete();
                    continue;
                }

                // Check if target already has this barcode
                $targetHasBarcode = ProductBarcode::where('product_id', $targetId)
                    ->where('barcode', $barcodeStr)
                    ->exists() || ($targetProduct->barcode === $barcodeStr);

                if ($targetHasBarcode) {
                    $barcodeRecord->delete();
                } else {
                    $barcodeRecord->update([
                        'product_id' => $targetId,
                        'is_primary' => false,
                    ]);
                }
            }

            // 11. Consolidate Variants / Children (ProductChild)
            $sourceChildren = ProductChild::where('product_id', $sourceId)->get();
            foreach ($sourceChildren as $child) {
                // Ensure unique SKU if colliding with existing SKU
                if ($child->sku) {
                    $skuExists = ProductChild::where('sku', $child->sku)->where('id', '!=', $child->id)->exists();
                    if ($skuExists) {
                        $child->sku = $child->sku . '-M' . $child->id;
                    }
                }
                $child->product_id = $targetId;
                $child->save();
            }

            // 12. Fill in missing product metadata on target if empty
            $fieldsToFill = [];
            if (empty($targetProduct->barcode) && !empty($sourceBarcode)) {
                // Check if barcode can be assigned to target without duplicate clash
                $barcodeClash = Product::where('barcode', $sourceBarcode)->where('id', '!=', $targetId)->exists();
                if (!$barcodeClash) {
                    $fieldsToFill['barcode'] = $sourceBarcode;
                }
            }
            if (empty($targetProduct->description) && !empty($sourceProduct->description)) {
                $fieldsToFill['description'] = $sourceProduct->description;
            }
            if (empty($targetProduct->image) && !empty($sourceProduct->image)) {
                $fieldsToFill['image'] = $sourceProduct->image;
            }
            if (empty($targetProduct->category_id) && !empty($sourceProduct->category_id)) {
                $fieldsToFill['category_id'] = $sourceProduct->category_id;
            }
            if (empty($targetProduct->subcategory_id) && !empty($sourceProduct->subcategory_id)) {
                $fieldsToFill['subcategory_id'] = $sourceProduct->subcategory_id;
            }
            if (empty($targetProduct->brand_id) && !empty($sourceProduct->brand_id)) {
                $fieldsToFill['brand_id'] = $sourceProduct->brand_id;
            }
            if (empty($targetProduct->tax_id) && !empty($sourceProduct->tax_id)) {
                $fieldsToFill['tax_id'] = $sourceProduct->tax_id;
            }
            if (empty($targetProduct->unit_id) && !empty($sourceProduct->unit_id)) {
                $fieldsToFill['unit_id'] = $sourceProduct->unit_id;
            }
            if (($targetProduct->purchase_price <= 0) && ($sourceProduct->purchase_price > 0)) {
                $fieldsToFill['purchase_price'] = $sourceProduct->purchase_price;
            }

            // 13. Update Weighted Average Cost if applicable
            if ($stockTarget > 0 && $stockSource > 0 && $combinedStock > 0) {
                $targetCost = $targetProduct->average_cost > 0 ? (float) $targetProduct->average_cost : (float) $targetProduct->purchase_price;
                $sourceCost = $sourceProduct->average_cost > 0 ? (float) $sourceProduct->average_cost : (float) $sourceProduct->purchase_price;
                if ($targetCost > 0 || $sourceCost > 0) {
                    $weightedCost = (($stockTarget * $targetCost) + ($stockSource * $sourceCost)) / $combinedStock;
                    $fieldsToFill['average_cost'] = round($weightedCost, 2);
                }
            }

            // 14. Update target stock and save
            $fieldsToFill['current_stock'] = $combinedStock;
            $targetProduct->update($fieldsToFill);

            // 15. Record explicit audit movement in Kardex
            $systemDocument = SystemDocument::findByCode('adjustment') 
                ?? SystemDocument::findByCode('initial_stock') 
                ?? SystemDocument::first();

            if ($systemDocument) {
                $docNumber = $systemDocument->generateNextNumber();
                InventoryMovement::create([
                    'system_document_id' => $systemDocument->id,
                    'document_number' => $docNumber,
                    'product_id' => $targetId,
                    'branch_id' => $targetProduct->branch_id ?? (auth()->user()?->branch_id ?? 1),
                    'user_id' => $userId ?? (auth()->id() ?? 1),
                    'movement_type' => $stockSource >= 0 ? 'in' : 'out',
                    'quantity' => abs($stockSource),
                    'stock_before' => $stockTarget,
                    'stock_after' => $combinedStock,
                    'unit_cost' => (float) ($sourceProduct->purchase_price ?: $targetProduct->purchase_price),
                    'total_cost' => ((float) ($sourceProduct->purchase_price ?: $targetProduct->purchase_price)) * abs($stockSource),
                    'notes' => "UNIFICACIÓN DE PRODUCTOS: Se fusionó con el producto '{$sourceName}' (SKU: {$sourceSku}, Código: " . ($sourceBarcode ?: 'N/A') . ", ID: {$sourceId}). Stock previo: {$stockTarget}, Stock transferido: {$stockSource}, Stock consolidado final: {$combinedStock}.",
                    'movement_date' => now(),
                ]);
            }

            // 16. Log system activity
            ActivityLogService::logDelete(
                'products',
                $sourceProduct,
                "Producto '{$sourceName}' (ID: {$sourceId}, SKU: {$sourceSku}) unificado y eliminado en favor de '{$targetProduct->name}' (ID: {$targetId})"
            );

            ActivityLogService::logUpdate(
                'products',
                $targetProduct,
                $oldTargetValues,
                "Producto '{$targetProduct->name}' (ID: {$targetId}) unificado con '{$sourceName}' (ID: {$sourceId}). Stock final: {$combinedStock}"
            );

            // 17. Delete source product
            $sourceProduct->delete();

            return [
                'success' => true,
                'target_product' => $targetProduct,
                'stock_previous' => $stockTarget,
                'stock_transferred' => $stockSource,
                'stock_final' => $combinedStock,
                'sales_count' => $salesCount,
                'purchases_count' => $purchasesCount,
                'movements_count' => $movementsCount,
            ];
        });
    }
}
