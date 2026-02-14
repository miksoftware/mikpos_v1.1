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

    public function edits(): HasMany
    {
        return $this->hasMany(CashReconciliationEdit::class);
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
     * Adjusts for overpayments (change/vuelto) to reflect actual sale value.
     */
    public function getTotalCashSalesAttribute(): float
    {
        $sales = $this->sales()->where('status', 'completed')->with('payments.paymentMethod')->get();
        $totalCash = 0;

        foreach ($sales as $sale) {
            $saleTotal = (float) $sale->total;
            $payments = $sale->payments;
            $paymentsSum = $payments->sum('amount');

            foreach ($payments as $payment) {
                $methodName = strtolower($payment->paymentMethod->name ?? '');
                $isCash = str_contains($methodName, 'efectivo') || str_contains($methodName, 'cash');

                if ($isCash) {
                    // Adjust if payments exceed sale total
                    if ($paymentsSum > $saleTotal && $paymentsSum > 0) {
                        $adjustedAmount = round(((float) $payment->amount / $paymentsSum) * $saleTotal, 2);
                    } else {
                        $adjustedAmount = (float) $payment->amount;
                    }
                    $totalCash += $adjustedAmount;
                }
            }
        }

        return round($totalCash, 2);
    }

    /**
     * Get sales grouped by payment method.
     * Adjusts payment amounts so they never exceed the sale total
     * (handles cases where cashier entered more than the total, e.g. change/vuelto).
     */
    public function getSalesByPaymentMethod(): \Illuminate\Support\Collection
    {
        $sales = $this->sales()->where('status', 'completed')->with('payments.paymentMethod')->get();

        $methodTotals = [];

        foreach ($sales as $sale) {
            $saleTotal = (float) $sale->total;
            $payments = $sale->payments;

            if ($payments->isEmpty()) {
                continue;
            }

            $paymentsSum = $payments->sum('amount');

            foreach ($payments as $payment) {
                $methodId = $payment->payment_method_id;
                $methodName = $payment->paymentMethod->name ?? 'Desconocido';

                // If payments exceed sale total, adjust proportionally
                if ($paymentsSum > $saleTotal && $paymentsSum > 0) {
                    $adjustedAmount = round(((float) $payment->amount / $paymentsSum) * $saleTotal, 2);
                } else {
                    $adjustedAmount = (float) $payment->amount;
                }

                if (!isset($methodTotals[$methodId])) {
                    $methodTotals[$methodId] = [
                        'method_id' => $methodId,
                        'method_name' => $methodName,
                        'total' => 0,
                        'count' => 0,
                    ];
                }

                $methodTotals[$methodId]['total'] = round($methodTotals[$methodId]['total'] + $adjustedAmount, 2);
                $methodTotals[$methodId]['count']++;
            }
        }

        return collect(array_values($methodTotals));
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
