<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'customer_type',
        'tax_document_id',
        'document_number',
        'first_name',
        'last_name',
        'business_name',
        'phone',
        'email',
        'department_id',
        'municipality_id',
        'address',
        'has_credit',
        'credit_limit',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'has_credit' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'credit_limit' => 'decimal:2',
        ];
    }

    /**
     * Scope to filter customers by branch.
     */
    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function taxDocument(): BelongsTo
    {
        return $this->belongsTo(TaxDocument::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function getFullNameAttribute(): string
    {
        if ($this->customer_type === 'juridico' && $this->business_name) {
            return $this->business_name;
        }
        
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->getFullNameAttribute();
        return "{$name} ({$this->document_number})";
    }
}