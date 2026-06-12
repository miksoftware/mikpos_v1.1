<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappConfig extends Model
{
    protected $fillable = [
        'branch_id',
        'phone_number_id',
        'waba_id',
        'token_permanente',
        'api_version',
        'phone_number_oficial',
        'template_name',
        'template_language',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
