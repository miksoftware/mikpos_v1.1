<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'prefix',
        'next_number',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'next_number' => 'integer',
    ];

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Generate the next document number and increment the counter
     */
    public function generateNextNumber(): string
    {
        $number = $this->prefix . '-' . str_pad($this->next_number, 5, '0', STR_PAD_LEFT);
        $this->increment('next_number');
        return $number;
    }

    /**
     * Get the current document number without incrementing
     */
    public function getCurrentNumber(): string
    {
        return $this->prefix . '-' . str_pad($this->next_number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Find a system document by its code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}
