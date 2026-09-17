<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceSetting extends Model
{
    use HasFactory;

    public const MODE_UNIFIED = 'unified';
    public const MODE_INDEPENDENT = 'independent';

    protected $fillable = [
        'store_mode',
        'default_branch_id',
        'allow_branch_switching',
        'auto_sync_customers',
        'additional_settings',
    ];

    protected function casts(): array
    {
        return [
            'allow_branch_switching' => 'boolean',
            'auto_sync_customers' => 'boolean',
            'additional_settings' => 'array',
        ];
    }

    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    /**
     * Get or create the singleton ecommerce settings instance.
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'store_mode' => self::MODE_UNIFIED,
                'allow_branch_switching' => true,
                'auto_sync_customers' => true,
            ]
        );
    }

    public static function isUnifiedMode(): bool
    {
        return static::getSettings()->store_mode === self::MODE_UNIFIED;
    }
}
