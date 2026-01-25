<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'branch_id',
        'user_id',
        'supplier_invoice',
        'purchase_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'status',
        'payment_status',
        'payment_type',
        'payment_method_id',
        'credit_amount',
        'paid_amount',
        'partial_payment_method_id',
        'payment_due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'due_date' => 'date',
            'payment_due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function partialPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'partial_payment_method_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    // Methods

    /**
     * Generate unique purchase number using SystemDocument.
     */
    public static function generatePurchaseNumber(): string
    {
        $systemDocument = SystemDocument::findByCode('purchase');
        
        if ($systemDocument && $systemDocument->is_active) {
            return $systemDocument->generateNextNumber();
        }
        
        // Fallback to old method if system document not found
        $prefix = 'CMP';
        $date = now()->format('Ymd');
        $lastPurchase = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastPurchase 
            ? (int) substr($lastPurchase->purchase_number, -4) + 1 
            : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Recalculate totals from items.
     */
    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }

    /**
     * Get status label in Spanish.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Borrador',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => $this->status,
        };
    }

    /**
     * Get payment status label in Spanish.
     */
    public function getPaymentStatusLabel(): string
    {
        return match($this->payment_status) {
            'pending' => 'Pendiente',
            'partial' => 'Parcial',
            'paid' => 'Pagada',
            default => $this->payment_status,
        };
    }

    /**
     * Get payment type label in Spanish.
     */
    public function getPaymentTypeLabel(): string
    {
        return match($this->payment_type) {
            'cash' => 'Contado',
            'credit' => 'Crédito',
            default => $this->payment_type,
        };
    }

    /**
     * Get remaining credit balance.
     */
    public function getRemainingCredit(): float
    {
        if ($this->payment_type !== 'credit') {
            return 0;
        }
        return (float) $this->credit_amount - (float) $this->paid_amount;
    }

    /**
     * Check if purchase can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if purchase can be completed.
     */
    public function canComplete(): bool
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    /**
     * Complete purchase and update stock with inventory movements.
     */
    public function complete(): bool
    {
        if (!$this->canComplete()) {
            return false;
        }

        // Update stock for each item and create inventory movements
        foreach ($this->items as $item) {
            $product = $item->product;
            if ($product) {
                // Create inventory movement
                InventoryMovement::createMovement(
                    'purchase',
                    $product,
                    'in',
                    $item->quantity,
                    (float) $item->unit_cost,
                    "Compra #{$this->purchase_number}",
                    $this,
                    $this->branch_id
                );

                // Update stock
                $product->increment('current_stock', $item->quantity);
                
                // Optionally update purchase price if different
                if ($item->unit_cost != $product->purchase_price) {
                    $product->update(['purchase_price' => $item->unit_cost]);
                }
            }
        }

        $this->status = 'completed';
        $this->save();

        return true;
    }

    /**
     * Cancel purchase and revert stock if was completed.
     */
    public function cancel(): bool
    {
        if ($this->status === 'cancelled') {
            return false;
        }

        // If was completed, revert stock and create reversal movements
        if ($this->status === 'completed') {
            foreach ($this->items as $item) {
                $product = $item->product;
                if ($product) {
                    // Create reversal inventory movement
                    InventoryMovement::createMovement(
                        'adjustment',
                        $product,
                        'out',
                        $item->quantity,
                        (float) $item->unit_cost,
                        "Anulación compra #{$this->purchase_number}",
                        $this,
                        $this->branch_id
                    );

                    $product->decrement('current_stock', $item->quantity);
                }
            }
        }

        $this->status = 'cancelled';
        $this->save();

        return true;
    }
}
