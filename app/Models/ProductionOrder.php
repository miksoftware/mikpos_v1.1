<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'status',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class);
    }

    /**
     * Cancel a completed production order, reverting all stock movements it generated:
     * subtracts the finished goods it added and restores the raw materials it consumed.
     */
    public function cancel(): bool
    {
        if ($this->status !== 'completed') {
            return false;
        }

        DB::transaction(function () {
            $this->loadMissing(['items.product', 'items.location', 'items.details.product']);

            foreach ($this->items as $item) {
                // Revert finished product stock added by this item
                $product = $item->product;
                if ($product) {
                    $unitCost = $item->quantity_to_produce > 0
                        ? (float) $item->total_cost / (float) $item->quantity_to_produce
                        : 0;

                    InventoryMovement::createMovement(
                        'adjustment',
                        $product,
                        'out',
                        (float) $item->quantity_to_produce,
                        $unitCost,
                        "Anulación de Orden de Producción #{$this->id}",
                        $this,
                        $this->branch_id,
                        $item->location_id
                    );

                    $product->decrement('current_stock', (float) $item->quantity_to_produce);

                    if ($item->location_id) {
                        $locationProduct = DB::table('location_products')
                            ->where('location_id', $item->location_id)
                            ->where('product_id', $product->id)
                            ->first();

                        if ($locationProduct) {
                            DB::table('location_products')
                                ->where('location_id', $item->location_id)
                                ->where('product_id', $product->id)
                                ->update([
                                    'quantity' => $locationProduct->quantity - $item->quantity_to_produce,
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                }

                // Restore ingredients consumed for this item
                foreach ($item->details as $detail) {
                    $ingredient = $detail->product;
                    if (!$ingredient) {
                        continue;
                    }

                    InventoryMovement::createMovement(
                        'adjustment',
                        $ingredient,
                        'in',
                        (float) $detail->quantity_consumed,
                        (float) $detail->unit_cost_at_time,
                        "Reverso de consumo por Anulación de Orden de Producción #{$this->id}",
                        $this,
                        $this->branch_id
                    );

                    $ingredient->increment('current_stock', (float) $detail->quantity_consumed);
                }
            }

            $this->status = 'cancelled';
            $this->save();
        });

        return true;
    }
}
