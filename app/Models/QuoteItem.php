<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'product_id',
        'product_child_id',
        'service_id',
        'combo_id',
        'product_name',
        'product_sku',
        'unit_price',
        'quantity',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'discount_type',
        'discount_type_value',
        'discount_amount',
        'discount_reason',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:3',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_type_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    // Relationships

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productChild(): BelongsTo
    {
        return $this->belongsTo(ProductChild::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function isService(): bool
    {
        return $this->service_id !== null;
    }

    public function isCombo(): bool
    {
        return $this->combo_id !== null;
    }
}
