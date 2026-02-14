<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashReconciliationEdit extends Model
{
    protected $fillable = [
        'cash_reconciliation_id',
        'user_id',
        'field_changed',
        'old_value',
        'new_value',
        'comment',
    ];

    public function cashReconciliation(): BelongsTo
    {
        return $this->belongsTo(CashReconciliation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
