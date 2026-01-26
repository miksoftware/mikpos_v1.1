<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'name',
        'number',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    // Methods

    /**
     * Generate the next cash register number for a branch.
     */
    public static function generateNumber(int $branchId): string
    {
        $lastRegister = static::where('branch_id', $branchId)
            ->whereNotNull('number')
            ->orderByRaw("CAST(number AS UNSIGNED) DESC")
            ->first();

        $nextNumber = 1;
        if ($lastRegister && $lastRegister->number) {
            $nextNumber = (int) $lastRegister->number + 1;
        }

        return str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
