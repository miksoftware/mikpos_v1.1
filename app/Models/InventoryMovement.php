<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_document_id',
        'document_number',
        'product_id',
        'branch_id',
        'user_id',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'movement_date' => 'date',
    ];

    public function systemDocument(): BelongsTo
    {
        return $this->belongsTo(SystemDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create an inventory movement for a product
     */
    public static function createMovement(
        string $documentCode,
        Product $product,
        string $movementType,
        float $quantity,
        ?float $unitCost = null,
        ?string $notes = null,
        ?Model $reference = null,
        ?int $branchId = null
    ): self {
        $systemDocument = SystemDocument::findByCode($documentCode);
        
        if (!$systemDocument) {
            throw new \Exception("System document with code '{$documentCode}' not found");
        }

        $stockBefore = $product->current_stock ?? 0;
        $stockAfter = $movementType === 'in' 
            ? $stockBefore + $quantity 
            : $stockBefore - $quantity;

        return static::create([
            'system_document_id' => $systemDocument->id,
            'document_number' => $systemDocument->generateNextNumber(),
            'product_id' => $product->id,
            'branch_id' => $branchId ?? auth()->user()->branch_id,
            'user_id' => auth()->id(),
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'unit_cost' => $unitCost,
            'total_cost' => $unitCost ? $unitCost * $quantity : null,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'notes' => $notes,
            'movement_date' => now(),
        ]);
    }
}
