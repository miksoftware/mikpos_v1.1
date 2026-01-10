<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductChild extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'name',
        'presentation_id',
        'color_id',
        'product_model_id',
        'size',
        'weight',
        'purchase_price',
        'sale_price',
        'price_includes_tax',
        'min_stock',
        'max_stock',
        'current_stock',
        'image',
        'imei',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'price_includes_tax' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock');
    }

    /**
     * Scope for POS searches - excludes inactive children AND children of inactive parents.
     * This ensures only sellable products appear in POS search results.
     */
    public function scopeForPosSearch(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('product', function (Builder $q) {
                $q->where('is_active', true);
            });
    }

    /**
     * Scope to search by name, SKU, or barcode for POS.
     * Only returns active children with active parents.
     */
    public function scopePosSearch(Builder $query, string $search): Builder
    {
        return $query->forPosSearch()
            ->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('product', function (Builder $productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
    }

    // Methods

    /**
     * Calculate the profit margin percentage.
     * Formula: ((sale_price - purchase_price) / purchase_price) * 100
     * Returns null if purchase_price is zero to avoid division by zero.
     */
    public function getMargin(): ?float
    {
        if ($this->purchase_price <= 0) {
            return null;
        }

        return round((($this->sale_price - $this->purchase_price) / $this->purchase_price) * 100, 2);
    }

    /**
     * Check if the product is low on stock.
     * Returns true if current_stock <= min_stock.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    /**
     * Get the display image with fallback to parent product image.
     * Returns the child's image if available, otherwise the parent's image,
     * or null if neither has an image.
     */
    public function getDisplayImage(): ?string
    {
        if ($this->image) {
            return $this->image;
        }

        return $this->product?->image;
    }

    /**
     * Get the display image URL with fallback to parent image or placeholder.
     * Returns the full URL to the image or a placeholder SVG data URI.
     */
    public function getDisplayImageUrl(): string
    {
        $imagePath = $this->getDisplayImage();
        
        if ($imagePath) {
            return \Illuminate\Support\Facades\Storage::url($imagePath);
        }

        // Return a placeholder SVG as data URI
        return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>');
    }

    // Accessors for inherited fields from parent

    protected function categoryId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->product?->category_id,
        );
    }

    protected function subcategoryId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->product?->subcategory_id,
        );
    }

    protected function brandId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->product?->brand_id,
        );
    }

    protected function taxId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->product?->tax_id,
        );
    }

    /**
     * Get the category through the parent product.
     */
    public function getCategory(): ?Category
    {
        return $this->product?->category;
    }

    /**
     * Get the subcategory through the parent product.
     */
    public function getSubcategory(): ?Subcategory
    {
        return $this->product?->subcategory;
    }

    /**
     * Get the brand through the parent product.
     */
    public function getBrand(): ?Brand
    {
        return $this->product?->brand;
    }

    /**
     * Get the tax through the parent product.
     */
    public function getTax(): ?Tax
    {
        return $this->product?->tax;
    }

    /**
     * Get the unit through the parent product.
     */
    public function getUnit(): ?Unit
    {
        return $this->product?->unit;
    }

    /**
     * Check if the sale price is below purchase price (negative margin).
     */
    public function hasNegativeMargin(): bool
    {
        return $this->sale_price < $this->purchase_price;
    }

    /**
     * Get the full product name including parent name.
     */
    public function getFullNameAttribute(): string
    {
        $parentName = $this->product?->name ?? '';
        return trim($parentName . ' - ' . $this->name, ' -');
    }
}
