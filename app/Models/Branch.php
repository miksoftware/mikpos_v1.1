<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'tax_id',
        'department_id',
        'municipality_id',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
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
