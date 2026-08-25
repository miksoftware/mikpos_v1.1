<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicPayrollSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'environment',
        'api_url',
        'payroll_numbering_range_id',
        'payroll_numbering_range_prefix',
        'adjustment_numbering_range_id',
        'adjustment_numbering_range_prefix',
        'additional_settings',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'additional_settings' => 'array',
        ];
    }

    /**
     * Get or create singleton setting record.
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'is_enabled' => false,
            'environment' => 'sandbox',
        ]);
    }
}
