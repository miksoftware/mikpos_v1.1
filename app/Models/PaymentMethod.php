<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = ['dian_code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Check if this payment method is cash.
     */
    public function isCash(): bool
    {
        $name = strtolower($this->name);
        return str_contains($name, 'efectivo') || str_contains($name, 'cash');
    }

    /**
     * Get the cash payment method.
     */
    public static function getCashMethod(): ?self
    {
        return static::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%efectivo%')
                  ->orWhere('name', 'like', '%cash%');
            })
            ->first();
    }
}
