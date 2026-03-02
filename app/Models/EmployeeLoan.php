<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id', 'concept', 'total_amount', 'monthly_deduction',
        'remaining_balance', 'start_date', 'end_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
