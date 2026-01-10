<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'subcategory_id',
        'brand_id',
        'unit_id',
        'tax_id',
        'image',
        'purchase_price',
        'sale_price',
        'price_includes_tax',
        'min_stock',
        'max_stock',
        'current_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_includes_tax' => 'boolean',
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    // Relationships

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductChild::class);
    }

    public function activeChildren(): HasMany
    {
        return $this->hasMany(ProductChild::class)->where('is_active', true);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for POS searches - only active products with at least one active child.
     * This ensures only sellable products appear in POS search results.
     */
    public function scopeForPosSearch(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('children', function (Builder $q) {
                $q->where('is_active', true);
            });
    }

    /**
     * Scope to search products for POS by name, SKU, or barcode.
     * Only returns active products with active children.
     */
    public function scopePosSearch(Builder $query, string $search): Builder
    {
        return $query->forPosSearch()
            ->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('children', function (Builder $childQuery) use ($search) {
                        $childQuery->where('is_active', true)
                            ->where(function (Builder $cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('sku', 'like', "%{$search}%")
                                    ->orWhere('barcode', 'like', "%{$search}%");
                            });
                    });
            });
    }

    // Methods

    /**
     * Generate a unique SKU for the product.
     * Format: CAT-XXXXX where CAT is category abbreviation and XXXXX is a unique number.
     */
    public function generateSku(): string
    {
        $prefix = 'PRD';
        
        if ($this->category) {
            // Use first 3 letters of category name, uppercase
            $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $this->category->name), 0, 3));
            if (strlen($prefix) < 3) {
                $prefix = str_pad($prefix, 3, 'X');
            }
        }

        // Find the highest existing SKU number with this prefix (MySQL compatible)
        $prefixLength = strlen($prefix) + 2; // prefix + '-'
        $lastProduct = static::where('sku', 'like', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING(sku, {$prefixLength}) AS UNSIGNED) DESC")
            ->first();

        $nextNumber = 1;
        if ($lastProduct && $lastProduct->sku) {
            $parts = explode('-', $lastProduct->sku);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        $sku = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        while (static::where('sku', $sku)->exists()) {
            $nextNumber++;
            $sku = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        $this->sku = $sku;
        return $sku;
    }

    /**
     * Calculate profit margin percentage.
     */
    public function getMargin(): ?float
    {
        if ($this->purchase_price <= 0) {
            return null;
        }
        return (($this->sale_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    /**
     * Check if sale price is less than purchase price.
     */
    public function hasNegativeMargin(): bool
    {
        return $this->sale_price < $this->purchase_price;
    }

    /**
     * Check if current stock is at or below minimum stock level.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    /**
     * Check if the product can be deleted.
     * A product cannot be deleted if it has active children.
     */
    public function canDelete(): bool
    {
        return $this->activeChildren()->count() === 0;
    }

    /**
     * Get the count of active children.
     */
    public function getActiveChildrenCountAttribute(): int
    {
        return $this->activeChildren()->count();
    }

    /**
     * Get the total children count.
     */
    public function getChildrenCountAttribute(): int
    {
        return $this->children()->count();
    }

    /**
     * Get the display image path.
     * Returns the product's image or null if no image exists.
     */
    public function getDisplayImage(): ?string
    {
        return $this->image;
    }

    /**
     * Get the display image URL with fallback to placeholder.
     * Returns the full URL to the image or a placeholder SVG data URI.
     */
    public function getDisplayImageUrl(): string
    {
        if ($this->image) {
            return \Illuminate\Support\Facades\Storage::url($this->image);
        }

        // Return a placeholder SVG as data URI
        return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>');
    }
}
