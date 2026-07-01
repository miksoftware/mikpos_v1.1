<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'product_id',
        'quantity_consumed',
        'unit_cost_at_time',
    ];

    protected function casts(): array
    {
        return [
            'quantity_consumed' => 'decimal:3',
            'unit_cost_at_time' => 'decimal:2',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
