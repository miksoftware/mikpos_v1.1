<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'sku',
        'name',
        'description',
        'category_id',
        'tax_id',
        'image',
        'cost',
        'sale_price',
        'price_includes_tax',
        'is_active',
        'has_commission',
        'commission_type',
        'commission_value',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_includes_tax' => 'boolean',
            'has_commission' => 'boolean',
            'cost' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'commission_value' => 'decimal:2',
        ];
    }

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
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
     * Generate a unique SKU for the service.
     */
    public function generateSku(): string
    {
        $prefix = 'SRV';

        $prefixLength = strlen($prefix) + 2;
        $lastService = static::where('sku', 'like', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING(sku, {$prefixLength}) AS UNSIGNED) DESC")
            ->first();

        $nextNumber = 1;
        if ($lastService && $lastService->sku) {
            $parts = explode('-', $lastService->sku);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        $sku = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        while (static::where('sku', $sku)->exists()) {
            $nextNumber++;
            $sku = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        $this->sku = $sku;
        return $sku;
    }

    /**
     * Get the sale price without tax.
     */
    public function getSalePriceWithoutTax(): float
    {
        if (!$this->price_includes_tax || !$this->tax) {
            return (float) $this->sale_price;
        }
        
        $taxRate = $this->tax->value / 100;
        return $this->sale_price / (1 + $taxRate);
    }

    /**
     * Get the sale price with tax.
     */
    public function getSalePriceWithTax(): float
    {
        if ($this->price_includes_tax || !$this->tax) {
            return (float) $this->sale_price;
        }
        
        $taxRate = $this->tax->value / 100;
        return $this->sale_price * (1 + $taxRate);
    }

    /**
     * Calculate profit margin.
     */
    public function getMargin(): ?float
    {
        if ($this->cost <= 0) {
            return null;
        }
        
        $salePrice = $this->getSalePriceWithoutTax();
        return (($salePrice - $this->cost) / $this->cost) * 100;
    }

    /**
     * Get the profit.
     */
    public function getProfit(): float
    {
        return $this->getSalePriceWithoutTax() - $this->cost;
    }

    /**
     * Get the commission amount.
     */
    public function getCommissionAmount(): float
    {
        if (!$this->has_commission || !$this->commission_value) {
            return 0;
        }

        $salePrice = $this->getSalePriceWithoutTax();

        if ($this->commission_type === 'percentage') {
            return $salePrice * ($this->commission_value / 100);
        }

        return (float) $this->commission_value;
    }
}
