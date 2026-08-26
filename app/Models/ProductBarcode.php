<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProductBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_child_id',
        'barcode',
        'description',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    // Relationships

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productChild(): BelongsTo
    {
        return $this->belongsTo(ProductChild::class);
    }

    // Scopes

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId)->whereNull('product_child_id');
    }

    public function scopeForProductChild(Builder $query, int $productChildId): Builder
    {
        return $query->where('product_child_id', $productChildId);
    }

    /**
     * Find a product or product child by barcode, optionally scoped to a branch.
     * Returns an array with 'type' ('product' or 'child') and the model instance.
     */
    public static function findByBarcode(string $barcode, ?int $branchId = null): ?array
    {
        $query = static::where('barcode', $barcode);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereHas('product', fn($p) => $p->where('branch_id', $branchId))
                  ->orWhereHas('productChild.product', fn($p) => $p->where('branch_id', $branchId));
            });
        }

        $barcodeRecord = $query->first();

        if (!$barcodeRecord) {
            return null;
        }

        if ($barcodeRecord->product_child_id) {
            return [
                'type' => 'child',
                'model' => $barcodeRecord->productChild,
                'barcode' => $barcodeRecord,
            ];
        }

        if ($barcodeRecord->product_id) {
            return [
                'type' => 'product',
                'model' => $barcodeRecord->product,
                'barcode' => $barcodeRecord,
            ];
        }

        return null;
    }

    /**
     * Check if a barcode already exists globally or in a branch.
     */
    public static function barcodeExists(string $barcode, ?int $excludeId = null, ?int $branchId = null): bool
    {
        if ($branchId) {
            return static::barcodeExistsInBranch($barcode, $branchId, excludeBarcodeId: $excludeId);
        }

        $query = static::where('barcode', $barcode);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if a barcode already exists in a specific branch.
     *
     * @param string $barcode
     * @param int|null $branchId
     * @param int|null $excludeBarcodeId ProductBarcode id to exclude
     * @param int|null $excludeProductId Product id to exclude
     * @param int|null $excludeChildId ProductChild id to exclude
     * @return bool
     */
    public static function barcodeExistsInBranch(
        string $barcode,
        ?int $branchId = null,
        ?int $excludeBarcodeId = null,
        ?int $excludeProductId = null,
        ?int $excludeChildId = null
    ): bool {
        $barcode = trim($barcode);
        if (empty($barcode)) {
            return false;
        }

        // If no branchId provided, check globally across all tables
        if (!$branchId) {
            $query = static::where('barcode', $barcode);
            if ($excludeBarcodeId) {
                $query->where('id', '!=', $excludeBarcodeId);
            }
            if ($excludeProductId) {
                $query->where(function ($q) use ($excludeProductId) {
                    $q->whereNull('product_id')->orWhere('product_id', '!=', $excludeProductId);
                });
            }
            if ($excludeChildId) {
                $query->where(function ($q) use ($excludeChildId) {
                    $q->whereNull('product_child_id')->orWhere('product_child_id', '!=', $excludeChildId);
                });
            }
            if ($query->exists()) {
                return true;
            }

            $productQuery = Product::where('barcode', $barcode);
            if ($excludeProductId) {
                $productQuery->where('id', '!=', $excludeProductId);
            }
            if ($productQuery->exists()) {
                return true;
            }

            $childQuery = ProductChild::where('barcode', $barcode);
            if ($excludeChildId) {
                $childQuery->where('id', '!=', $excludeChildId);
            }
            return $childQuery->exists();
        }

        // 1. Check in product_barcodes table
        $barcodeQuery = static::where('barcode', $barcode);

        if ($excludeBarcodeId) {
            $barcodeQuery->where('id', '!=', $excludeBarcodeId);
        }

        $barcodeQuery->where(function ($q) use ($branchId, $excludeProductId, $excludeChildId) {
            // Parent barcodes in this branch
            $q->where(function ($parentQ) use ($branchId, $excludeProductId) {
                $parentQ->whereNull('product_child_id')
                    ->whereHas('product', function ($pQuery) use ($branchId, $excludeProductId) {
                        $pQuery->where('branch_id', $branchId);
                        if ($excludeProductId) {
                            $pQuery->where('id', '!=', $excludeProductId);
                        }
                    });
            })
            // Or Variant barcodes in this branch
            ->orWhere(function ($childQ) use ($branchId, $excludeChildId) {
                $childQ->whereNotNull('product_child_id');
                if ($excludeChildId) {
                    $childQ->where('product_child_id', '!=', $excludeChildId);
                }
                $childQ->whereHas('productChild.product', function ($pQuery) use ($branchId) {
                    $pQuery->where('branch_id', $branchId);
                });
            });
        });

        if ($barcodeQuery->exists()) {
            return true;
        }

        // 2. Also check direct products.barcode column in this branch
        $productQuery = Product::where('barcode', $barcode)->where('branch_id', $branchId);
        if ($excludeProductId) {
            $productQuery->where('id', '!=', $excludeProductId);
        }
        if ($productQuery->exists()) {
            return true;
        }

        // 3. Also check direct product_children.barcode column in this branch
        $childQuery = ProductChild::where('barcode', $barcode)
            ->whereHas('product', function ($pQuery) use ($branchId) {
                $pQuery->where('branch_id', $branchId);
            });
        if ($excludeChildId) {
            $childQuery->where('id', '!=', $excludeChildId);
        }

        return $childQuery->exists();
    }
}
