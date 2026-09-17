<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CreditPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'receipt_number',
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
        return $this->belongsTo(User::class)->withTrashed();
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

    public function getEntityNameAttribute(): string
    {
        if ($this->customer) {
            return $this->customer->customer_type === 'juridico'
                ? ($this->customer->business_name ?: $this->customer->full_name)
                : $this->customer->full_name;
        }
        if ($this->supplier) {
            return $this->supplier->name;
        }
        return 'Consumidor Final / General';
    }

    public function getInvoiceNumberAttribute(): ?string
    {
        if ($this->sale) {
            return $this->sale->invoice_number;
        }
        if ($this->purchase) {
            return $this->purchase->purchase_number;
        }
        return null;
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'PG';
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            $last = static::where('payment_number', 'like', "{$prefix}-%")
                ->orderBy('id', 'desc')
                ->first();
        } else {
            $last = static::where('payment_number', 'like', "{$prefix}-%")
                ->orderByRaw("CAST(SUBSTRING_INDEX(payment_number, '-', -1) AS UNSIGNED) DESC")
                ->first();
        }

        $sequence = 1;
        if ($last && $last->payment_number) {
            $parts = explode('-', $last->payment_number);
            $lastSeq = (int) end($parts);
            if ($lastSeq > 0) {
                $sequence = $lastSeq + 1;
            }
        }

        do {
            $candidate = sprintf('%s-%08d', $prefix, $sequence);
            $sequence++;
        } while (static::where('payment_number', $candidate)->exists());

        return $candidate;
    }

    public static function generateReceiptNumber(string $type = 'receivable', ?string $date = null): string
    {
        $prefix = $type === 'payable' ? 'EGR' : 'RC';
        $dateStr = $date ? str_replace('-', '', substr($date, 0, 10)) : now()->format('Ymd');
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        if ($isSqlite) {
            $last = static::where('receipt_number', 'like', "{$prefix}-{$dateStr}-%")
                ->orderBy('id', 'desc')
                ->first();
        } else {
            $last = static::where('receipt_number', 'like', "{$prefix}-{$dateStr}-%")
                ->orderByRaw("CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED) DESC")
                ->first();
        }

        $sequence = 1;
        if ($last && $last->receipt_number) {
            $parts = explode('-', $last->receipt_number);
            $lastSeq = (int) end($parts);
            if ($lastSeq > 0) {
                $sequence = $lastSeq + 1;
            }
        }

        do {
            $candidate = sprintf('%s-%s-%04d', $prefix, $dateStr, $sequence);
            $sequence++;
        } while (static::where('receipt_number', $candidate)->exists());

        return $candidate;
    }
}
