<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'combo_id',
        'product_id',
        'product_child_id',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    // Relationships

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productChild(): BelongsTo
    {
        return $this->belongsTo(ProductChild::class);
    }

    // Methods

    /**
     * Get the product name (either parent or child).
     */
    public function getProductName(): string
    {
        if ($this->product_child_id && $this->productChild) {
            return $this->productChild->full_name;
        }
        
        if ($this->product_id && $this->product) {
            return $this->product->name;
        }
        
        return 'Producto no encontrado';
    }

    /**
     * Get the product image.
     */
    public function getProductImage(): ?string
    {
        if ($this->product_child_id && $this->productChild) {
            return $this->productChild->getDisplayImage();
        }
        
        if ($this->product_id && $this->product) {
            return $this->product->image;
        }
        
        return null;
    }

    /**
     * Get the subtotal for this item.
     */
    public function getSubtotal(): float
    {
        return $this->unit_price * $this->quantity;
    }

    /**
     * Check if this item has stock.
     */
    public function hasStock(): bool
    {
        if ($this->product_id && $this->product) {
            return $this->product->current_stock >= $this->quantity;
        }
        
        if ($this->product_child_id && $this->productChild && $this->productChild->product) {
            $requiredStock = $this->quantity * $this->productChild->unit_quantity;
            return $this->productChild->product->current_stock >= $requiredStock;
        }
        
        return false;
    }
}
