<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'credit_type',
        'purchase_id',
        'sale_id',
        'customer_id',
        'supplier_id',
        'branch_id',
        'user_id',
        'payment_method_id',
        'cash_reconciliation_id',
        'amount',
        'affects_cash',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'affects_cash' => 'boolean',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

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

    public function cashReconciliation(): BelongsTo
    {
        return $this->belongsTo(CashReconciliation::class);
    }

    public function isReceivable(): bool
    {
        return $this->credit_type === 'receivable';
    }

    public function isPayable(): bool
    {
        return $this->credit_type === 'payable';
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAG';
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->orderByDesc('id')->first();
        $sequence = 1;
        if ($last) {
            $parts = explode('-', $last->payment_number);
            if (count($parts) === 3 && is_numeric($parts[2])) {
                $sequence = (int) $parts[2] + 1;
            }
        }
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
