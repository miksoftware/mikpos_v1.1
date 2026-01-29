<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'cash_reconciliation_id',
        'customer_id',
        'user_id',
        'invoice_number',
        'cufe',
        'qr_code',
        'dian_public_url',
        'dian_number',
        'dian_validated_at',
        'dian_response',
        'is_electronic',
        'reference_code',
        'subtotal',
        'tax_total',
        'discount',
        'total',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'is_electronic' => 'boolean',
            'dian_validated_at' => 'datetime',
            'dian_response' => 'array',
        ];
    }

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashReconciliation(): BelongsTo
    {
        return $this->belongsTo(CashReconciliation::class);
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
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function reprints(): HasMany
    {
        return $this->hasMany(SaleReprint::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    // Scopes

    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeForReconciliation(Builder $query, int $reconciliationId): Builder
    {
        return $query->where('cash_reconciliation_id', $reconciliationId);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    // Methods

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get cash payment amount (for cash register tracking).
     */
    public function getCashPaymentAttribute(): float
    {
        return (float) $this->payments()
            ->whereHas('paymentMethod', function ($q) {
                $q->where('name', 'like', '%efectivo%')
                  ->orWhere('name', 'like', '%cash%');
            })
            ->sum('amount');
    }

    /**
     * Generate next invoice number.
     */
    public static function generateInvoiceNumber(int $branchId): string
    {
        $prefix = 'FAC';
        $date = now()->format('Ymd');
        
        $lastSale = static::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();
        
        $sequence = 1;
        if ($lastSale) {
            // Extract sequence from last invoice number
            $parts = explode('-', $lastSale->invoice_number);
            if (count($parts) === 3) {
                $sequence = (int) $parts[2] + 1;
            }
        }
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
