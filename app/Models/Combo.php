<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Combo extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'image',
        'combo_price',
        'original_price',
        'limit_type',
        'start_date',
        'end_date',
        'max_sales',
        'current_sales',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'combo_price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'max_sales' => 'integer',
            'current_sales' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter combos by branch.
     */
    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->where(function ($q) {
            $q->where(function ($subQ) {
                // Sin límite
                $subQ->where('limit_type', 'none');
            })->orWhere(function ($subQ) {
                // Por tiempo - dentro del rango
                $now = Carbon::now();
                $subQ->whereIn('limit_type', ['time', 'both'])
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('start_date')
                            ->orWhere('start_date', '<=', $now);
                    })
                    ->where(function ($dateQ) use ($now) {
                        $dateQ->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
            })->orWhere(function ($subQ) {
                // Por cantidad - no ha alcanzado el máximo
                $subQ->whereIn('limit_type', ['quantity', 'both'])
                    ->whereColumn('current_sales', '<', 'max_sales');
            });
        });
    }

    // Methods

    /**
     * Calculate the savings amount.
     */
    public function getSavings(): float
    {
        return max(0, $this->original_price - $this->combo_price);
    }

    /**
     * Calculate the savings percentage.
     */
    public function getSavingsPercentage(): float
    {
        if ($this->original_price <= 0) {
            return 0;
        }
        return round(($this->getSavings() / $this->original_price) * 100, 1);
    }

    /**
     * Check if the combo is currently available for sale.
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check time limit
        if (in_array($this->limit_type, ['time', 'both'])) {
            $now = Carbon::now();
            if ($this->start_date && $now->lt($this->start_date)) {
                return false;
            }
            if ($this->end_date && $now->gt($this->end_date)) {
                return false;
            }
        }

        // Check quantity limit
        if (in_array($this->limit_type, ['quantity', 'both'])) {
            if ($this->max_sales && $this->current_sales >= $this->max_sales) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get remaining quantity if limited by quantity.
     */
    public function getRemainingQuantity(): ?int
    {
        if (!in_array($this->limit_type, ['quantity', 'both']) || !$this->max_sales) {
            return null;
        }
        return max(0, $this->max_sales - $this->current_sales);
    }

    /**
     * Get remaining time if limited by time.
     */
    public function getRemainingTime(): ?string
    {
        if (!in_array($this->limit_type, ['time', 'both']) || !$this->end_date) {
            return null;
        }
        
        $now = Carbon::now();
        if ($now->gt($this->end_date)) {
            return 'Expirado';
        }
        
        return $now->diffForHumans($this->end_date, ['parts' => 2]);
    }

    /**
     * Increment sales count.
     */
    public function incrementSales(int $quantity = 1): void
    {
        $this->increment('current_sales', $quantity);
    }

    /**
     * Recalculate original price from items.
     */
    public function recalculateOriginalPrice(): void
    {
        $total = $this->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });
        
        $this->update(['original_price' => $total]);
    }

    /**
     * Get the total number of products in the combo.
     */
    public function getTotalProductsCount(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if combo has enough stock for all items.
     */
    public function hasStock(): bool
    {
        foreach ($this->items as $item) {
            if ($item->product_id) {
                $product = $item->product;
                if ($product && $product->current_stock < $item->quantity) {
                    return false;
                }
            }
            // For child products, check parent stock
            if ($item->product_child_id) {
                $child = $item->productChild;
                if ($child && $child->product) {
                    $requiredStock = $item->quantity * $child->unit_quantity;
                    if ($child->product->current_stock < $requiredStock) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        if (!$this->is_active) {
            return 'Inactivo';
        }
        
        if (!$this->isAvailable()) {
            if (in_array($this->limit_type, ['quantity', 'both']) && 
                $this->max_sales && $this->current_sales >= $this->max_sales) {
                return 'Agotado';
            }
            if (in_array($this->limit_type, ['time', 'both'])) {
                $now = Carbon::now();
                if ($this->start_date && $now->lt($this->start_date)) {
                    return 'Próximamente';
                }
                if ($this->end_date && $now->gt($this->end_date)) {
                    return 'Expirado';
                }
            }
        }
        
        return 'Disponible';
    }
}
