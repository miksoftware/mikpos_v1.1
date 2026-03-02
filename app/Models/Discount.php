<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Discount extends Model
{
    protected $fillable = [
        'branch_id', 'name', 'description', 'scope', 'scope_id',
        'discount_type', 'discount_value', 'start_date', 'end_date',
        'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_product');
    }

    /**
     * Scope: only active discounts within date range.
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Get the human-readable scope label.
     */
    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {
            'all' => 'Todos los productos',
            'category' => 'Categoría',
            'subcategory' => 'Subcategoría',
            'brand' => 'Marca',
            'products' => 'Productos específicos',
            default => $this->scope,
        };
    }

    /**
     * Get the name of the scoped entity.
     */
    public function getScopeNameAttribute(): ?string
    {
        if ($this->scope === 'all') return null;

        if ($this->scope === 'products') {
            $count = $this->products()->count();
            return $count . ' producto' . ($count !== 1 ? 's' : '');
        }

        if (!$this->scope_id) return null;

        return match ($this->scope) {
            'category' => Category::find($this->scope_id)?->name,
            'subcategory' => Subcategory::find($this->scope_id)?->name,
            'brand' => Brand::find($this->scope_id)?->name,
            default => null,
        };
    }

    /**
     * Check if a product matches this discount.
     */
    public function appliesToProduct(Product $product): bool
    {
        return match ($this->scope) {
            'all' => true,
            'category' => $product->category_id == $this->scope_id,
            'subcategory' => $product->subcategory_id == $this->scope_id,
            'brand' => $product->brand_id == $this->scope_id,
            'products' => $this->products()->where('product_id', $product->id)->exists(),
            default => false,
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $today = now()->toDateString();
        if (!$this->is_active) return 'Inactivo';
        if ($this->start_date->toDateString() > $today) return 'Programado';
        if ($this->end_date->toDateString() < $today) return 'Expirado';
        return 'Activo';
    }

    /**
     * Find the best discount for a product in a branch.
     * Returns the discount with the highest effective value.
     */
    public static function findBestForProduct(Product $product, int $branchId): ?self
    {
        $discounts = static::active()
            ->where('branch_id', $branchId)
            ->get()
            ->filter(fn($d) => $d->appliesToProduct($product));

        if ($discounts->isEmpty()) return null;

        // Return the one that gives the biggest discount amount
        return $discounts->sortByDesc(function ($d) use ($product) {
            if ($d->discount_type === 'percentage') {
                return $product->sale_price * ($d->discount_value / 100);
            }
            return $d->discount_value;
        })->first();
    }
}
