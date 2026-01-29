<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'branch_id',
        'user_id',
        'cash_reconciliation_id',
        'number',
        'type',
        'reason',
        'subtotal',
        'tax_total',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    // Relationships

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashReconciliation(): BelongsTo
    {
        return $this->belongsTo(CashReconciliation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * Generate next refund number.
     */
    public static function generateNumber(int $branchId): string
    {
        $prefix = 'DEV';
        $date = now()->format('Ymd');
        
        $lastRefund = static::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();
        
        $sequence = 1;
        if ($lastRefund) {
            $parts = explode('-', $lastRefund->number);
            if (count($parts) === 3) {
                $sequence = (int) $parts[2] + 1;
            }
        }
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
