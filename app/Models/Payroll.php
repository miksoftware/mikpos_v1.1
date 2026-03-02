<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Payroll extends Model
{
    protected $fillable = [
        'branch_id', 'period_type', 'period_start', 'period_end',
        'payment_date', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        return $branchId ? $query->where('payrolls.branch_id', $branchId) : $query;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador',
            'calculada' => 'Calculada',
            'aprobada' => 'Aprobada',
            'pagada' => 'Pagada',
            default => $this->status,
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->period_start->format('d/m/Y') . ' - ' . $this->period_end->format('d/m/Y');
    }
}
