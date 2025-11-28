<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'tax_id',
        'province',
        'city',
        'address',
        'phone',
        'email',
        'ticket_prefix',
        'invoice_prefix',
        'receipt_prefix',
        'credit_note_prefix',
        'activity_number',
        'authorization_date',
        'receipt_header',
        'show_in_pos',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'authorization_date' => 'date',
            'show_in_pos' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }
}
