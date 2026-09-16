<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'cash_reconciliation_id',
        'customer_id',
        'user_id',
        'seller_id',
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
        'payment_type',
        'payment_status',
        'credit_amount',
        'paid_amount',
        'payment_due_date',
        'notes',
        'global_discount_type',
        'global_discount_value',
        'global_discount_amount',
        'global_discount_reason',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'is_electronic' => 'boolean',
            'dian_validated_at' => 'datetime',
            'dian_response' => 'array',
            'payment_due_date' => 'date',
            'global_discount_value' => 'decimal:2',
            'global_discount_amount' => 'decimal:2',
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
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withTrashed();
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

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    public function ecommerceOrder(): HasOne
    {
        return $this->hasOne(EcommerceOrder::class);
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

    public function scopeEcommerce(Builder $query): Builder
    {
        return $query->where('source', 'ecommerce');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }

    // Methods

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isEcommerce(): bool
    {
        return $this->source === 'ecommerce';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
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
     * Generate next invoice number with global continuous sequence.
     * Format: FAC-XXXX (no date, never resets, padded to 4 digits but expands as needed).
     *
     * The sequence is global across all branches and all dates. It works with both
     * the old format (FAC-YYYYMMDD-XXXX) and the new format (FAC-XXXX) by always
     * taking the LAST hyphen-separated segment as the numeric sequence.
     *
     * @param  int  $branchId  Kept for backwards compatibility; not used in the new format.
     */
    public static function generateInvoiceNumber(int $branchId): string
    {
        $prefix = 'FAC';

        // Find the highest sequence across ALL invoices (continuous numbering, no per-day reset).
        // Works with both old (FAC-YYYYMMDD-XXXX) and new (FAC-XXXX) formats by extracting
        // the last hyphen-separated segment as the numeric sequence.
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        if ($isSqlite) {
            $lastSale = static::where('invoice_number', 'like', "{$prefix}-%")
                ->orderBy('id', 'desc')
                ->first();
        } else {
            $lastSale = static::where('invoice_number', 'like', "{$prefix}-%")
                ->orderByRaw("CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED) DESC")
                ->first();
        }

        $sequence = 1;
        if ($lastSale) {
            $parts = explode('-', $lastSale->invoice_number);
            $lastSeq = (int) end($parts);
            $sequence = $lastSeq + 1;
        }

        return sprintf('%s-%s', $prefix, str_pad((string) $sequence, 4, '0', STR_PAD_LEFT));
    }

    /**
     * Get extracted main DIAN error message.
     */
    public function getDianErrorMessageAttribute(): ?string
    {
        if (!$this->dian_response || !is_array($this->dian_response)) {
            return null;
        }

        return $this->dian_response['data']['message']
            ?? $this->dian_response['message']
            ?? $this->dian_response['error']
            ?? null;
    }

    /**
     * Get extracted DIAN detailed errors array.
     */
    public function getDianErrorsListAttribute(): array
    {
        if (!$this->dian_response || !is_array($this->dian_response)) {
            return [];
        }

        $rawErrors = $this->dian_response['data']['errors']
            ?? $this->dian_response['errors']
            ?? [];

        if (!is_array($rawErrors)) {
            return $rawErrors ? [(string) $rawErrors] : [];
        }

        $list = [];
        foreach ($rawErrors as $key => $val) {
            if (is_array($val)) {
                $val = implode(', ', $val);
            }
            if (is_string($key) && !is_numeric($key)) {
                $list[] = "{$key}: {$val}";
            } else {
                $list[] = (string) $val;
            }
        }

        // If no structured errors in data.errors or errors, check if 'error' key exists
        if (empty($list) && isset($this->dian_response['error']) && is_string($this->dian_response['error'])) {
            $list[] = $this->dian_response['error'];
        }

        return $list;
    }

    /**
     * Get days overdue for credit sales.
     */
    public function getDaysOverdueAttribute(): int
    {
        if ($this->payment_type !== 'credit') {
            return 0;
        }

        $remaining = (float) $this->credit_amount - (float) $this->paid_amount;
        if ($remaining <= 0 || $this->payment_status === 'paid') {
            return 0;
        }

        $dueDate = $this->payment_due_date ?? ($this->created_at ? $this->created_at->copy()->addDays(30) : null);
        if (!$dueDate) {
            return 0;
        }

        $today = now()->startOfDay();
        $due = $dueDate->copy()->startOfDay();

        return $today->greaterThan($due) ? (int) $due->diffInDays($today) : 0;
    }
}
