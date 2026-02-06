<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'abbreviation', 'is_active', 'is_weight_unit'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_weight_unit' => 'boolean',
        ];
    }

    /**
     * Check if this unit is a weight-based unit.
     */
    public function isWeightUnit(): bool
    {
        return $this->is_weight_unit;
    }
}
