<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CashReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'cash_register_id',
        'opened_by',
        'closed_by',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'opening_notes',
        'closing_notes',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'closing_amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Scopes

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeForCashRegister(Builder $query, ?int $cashRegisterId = null): Builder
    {
        if ($cashRegisterId) {
            return $query->where('cash_register_id', $cashRegisterId);
        }
        return $query;
    }

    // Methods

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Get total income from movements.
     */
    public function getTotalIncomeAttribute(): float
    {
        return (float) $this->movements()->where('type', 'income')->sum('amount');
    }

    /**
     * Get total expenses from movements.
     */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->movements()->where('type', 'expense')->sum('amount');
    }

    /**
     * Get total sales amount.
     */
    public function getTotalSalesAttribute(): float
    {
        return (float) $this->sales()->where('status', 'completed')->sum('total');
    }

    /**
     * Get total cash sales (only cash payments affect the register).
     */
    public function getTotalCashSalesAttribute(): float
    {
        return (float) \App\Models\SalePayment::whereHas('sale', function ($q) {
            $q->where('cash_reconciliation_id', $this->id)
              ->where('status', 'completed');
        })->whereHas('paymentMethod', function ($q) {
            $q->where('name', 'like', '%efectivo%')
              ->orWhere('name', 'like', '%cash%');
        })->sum('amount');
    }

    /**
     * Get sales grouped by payment method.
     */
    public function getSalesByPaymentMethod(): \Illuminate\Support\Collection
    {
        return \App\Models\SalePayment::with('paymentMethod')
            ->whereHas('sale', function ($q) {
                $q->where('cash_reconciliation_id', $this->id)
                  ->where('status', 'completed');
            })
            ->get()
            ->groupBy('payment_method_id')
            ->map(function ($payments) {
                $method = $payments->first()->paymentMethod;
                return [
                    'method_id' => $method->id,
                    'method_name' => $method->name,
                    'total' => $payments->sum('amount'),
                    'count' => $payments->count(),
                ];
            })
            ->values();
    }

    /**
     * Get sales count.
     */
    public function getSalesCountAttribute(): int
    {
        return $this->sales()->where('status', 'completed')->count();
    }

    /**
     * Calculate expected amount based on opening + cash sales + income - expenses.
     * Only cash affects the physical register.
     */
    public function calculateExpectedAmount(): float
    {
        return (float) $this->opening_amount 
            + $this->total_cash_sales 
            + $this->total_income 
            - $this->total_expenses;
    }

    /**
     * Check if a cash register has an open reconciliation.
     */
    public static function hasOpenReconciliation(int $cashRegisterId): bool
    {
        return static::where('cash_register_id', $cashRegisterId)
            ->where('status', 'open')
            ->exists();
    }

    /**
     * Get the open reconciliation for a cash register.
     */
    public static function getOpenReconciliation(int $cashRegisterId): ?self
    {
        return static::where('cash_register_id', $cashRegisterId)
            ->where('status', 'open')
            ->first();
    }
}
