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
     * Find a product or product child by barcode.
     * Returns an array with 'type' ('product' or 'child') and the model instance.
     */
    public static function findByBarcode(string $barcode): ?array
    {
        $barcodeRecord = static::where('barcode', $barcode)->first();

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
     * Check if a barcode already exists.
     */
    public static function barcodeExists(string $barcode, ?int $excludeId = null): bool
    {
        $query = static::where('barcode', $barcode);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
