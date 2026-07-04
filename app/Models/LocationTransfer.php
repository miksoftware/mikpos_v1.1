<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocationTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'branch_id',
        'from_location_id',
        'to_location_id',
        'user_id',
        'notes',
    ];

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LocationTransferItem::class);
    }

    /**
     * Generate the next transfer number: TUB-XXXX
     */
    public static function generateTransferNumber(): string
    {
        $prefix = 'TUB';
        $last = static::where('transfer_number', 'like', "{$prefix}-%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(transfer_number, '-', -1) AS UNSIGNED) DESC")
            ->first();

        $sequence = 1;
        if ($last) {
            $parts = explode('-', $last->transfer_number);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('%s-%s', $prefix, str_pad((string) $sequence, 4, '0', STR_PAD_LEFT));
    }
}
