<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'customer_id',
        'user_id',
        'quote_number',
        'valid_until',
        'subtotal',
        'tax_total',
        'discount',
        'total',
        'global_discount_type',
        'global_discount_value',
        'global_discount_amount',
        'global_discount_reason',
        'status',
        'reserves_inventory',
        'converted_to_sale_id',
        'converted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'global_discount_value' => 'decimal:2',
            'global_discount_amount' => 'decimal:2',
            'converted_at' => 'datetime',
            'reserves_inventory' => 'boolean',
        ];
    }

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function convertedToSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_to_sale_id');
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    // Scopes

    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', 'converted');
    }

    // Helpers

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        if (!$this->valid_until) {
            return false;
        }
        return $this->valid_until->isPast() && $this->status === 'draft';
    }

    /**
     * Reserve inventory for all product items in this quote.
     * Creates 'out' inventory movements (type: quote_reservation) and decrements current_stock.
     * Only processes items that are actual products with manages_inventory = true.
     * Safe to call multiple times — skips if already reserved.
     */
    public function reserveInventory(): void
    {
        if ($this->reserves_inventory) {
            return; // Already reserved
        }

        $this->load('items');

        foreach ($this->items as $item) {
            if (!$item->product_id || $item->service_id || $item->combo_id) {
                continue;
            }

            $product = Product::find($item->product_id);
            if (!$product || !$product->manages_inventory) {
                continue;
            }

            $qty = (float) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            InventoryMovement::createMovement(
                'quote_reservation',
                $product,
                'out',
                $qty,
                (float) $product->average_cost > 0 ? (float) $product->average_cost : (float) $product->purchase_price,
                "Reserva cotización #{$this->quote_number}",
                $this,
                $this->branch_id
            );

            $product->decrement('current_stock', $qty);
        }

        $this->update(['reserves_inventory' => true]);
    }

    /**
     * Release reserved inventory back to stock.
     * Creates 'in' inventory movements to reverse the reservation.
     * Safe to call if not reserved — does nothing in that case.
     */
    public function releaseInventory(): void
    {
        if (!$this->reserves_inventory) {
            return; // Nothing to release
        }

        $this->load('items');

        foreach ($this->items as $item) {
            if (!$item->product_id || $item->service_id || $item->combo_id) {
                continue;
            }

            $product = Product::find($item->product_id);
            if (!$product || !$product->manages_inventory) {
                continue;
            }

            $qty = (float) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            InventoryMovement::createMovement(
                'adjustment',
                $product,
                'in',
                $qty,
                (float) $product->average_cost > 0 ? (float) $product->average_cost : (float) $product->purchase_price,
                "Liberación reserva cotización #{$this->quote_number}",
                $this,
                $this->branch_id
            );

            $product->increment('current_stock', $qty);
        }

        $this->update(['reserves_inventory' => false]);
    }

    /**
     * Generate next quote number with global continuous sequence.
     * Format: COT-XXXX (no date, never resets, padded to 4 digits but expands as needed).
     */
    public static function generateQuoteNumber(): string
    {
        $prefix = 'COT';

        // Find the highest sequence across ALL branches and ALL dates (continuous numbering)
        $lastQuote = static::where('quote_number', 'like', "{$prefix}-%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(quote_number, '-', -1) AS UNSIGNED) DESC")
            ->first();

        $sequence = 1;
        if ($lastQuote) {
            $parts = explode('-', $lastQuote->quote_number);
            // Last segment is the number
            $lastSeq = (int) end($parts);
            $sequence = $lastSeq + 1;
        }

        // Pad to 4 digits minimum, but allow growth beyond
        return sprintf('%s-%s', $prefix, str_pad((string) $sequence, 4, '0', STR_PAD_LEFT));
    }
}
