<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDetail extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id', 'worked_days',
        'base_salary_earned', 'transport_allowance_earned',
        'overtime_daytime_hours', 'overtime_daytime_value',
        'overtime_nighttime_hours', 'overtime_nighttime_value',
        'overtime_sunday_daytime_hours', 'overtime_sunday_daytime_value',
        'overtime_sunday_nighttime_hours', 'overtime_sunday_nighttime_value',
        'night_surcharge_hours', 'night_surcharge_value',
        'sunday_holiday_hours', 'sunday_holiday_value',
        'commissions', 'bonuses', 'disability_days', 'disability_value',
        'vacation_days', 'vacation_value', 'other_income', 'total_earned',
        'health_employee', 'pension_employee', 'solidarity_fund',
        'income_tax_withholding', 'loan_deduction', 'cooperative_deduction',
        'libranza_deduction', 'other_deductions', 'total_deductions',
        'health_employer', 'pension_employer', 'arl_employer',
        'sena_employer', 'icbf_employer', 'compensation_fund_employer',
        'severance_provision', 'severance_interest_provision',
        'service_bonus_provision', 'vacation_provision',
        'net_pay',
    ];

    protected function casts(): array
    {
        return [
            'worked_days' => 'decimal:2',
            'base_salary_earned' => 'decimal:2',
            'transport_allowance_earned' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'commissions' => 'decimal:2',
            'bonuses' => 'decimal:2',
            'other_income' => 'decimal:2',
            'health_employee' => 'decimal:2',
            'pension_employee' => 'decimal:2',
            'solidarity_fund' => 'decimal:2',
            'income_tax_withholding' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'health_employer' => 'decimal:2',
            'pension_employer' => 'decimal:2',
            'arl_employer' => 'decimal:2',
            'sena_employer' => 'decimal:2',
            'icbf_employer' => 'decimal:2',
            'compensation_fund_employer' => 'decimal:2',
            'severance_provision' => 'decimal:2',
            'severance_interest_provision' => 'decimal:2',
            'service_bonus_provision' => 'decimal:2',
            'vacation_provision' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTotalEmployerCostAttribute(): float
    {
        return (float) $this->health_employer + (float) $this->pension_employer
            + (float) $this->arl_employer + (float) $this->sena_employer
            + (float) $this->icbf_employer + (float) $this->compensation_fund_employer;
    }

    public function getTotalProvisionsAttribute(): float
    {
        return (float) $this->severance_provision + (float) $this->severance_interest_provision
            + (float) $this->service_bonus_provision + (float) $this->vacation_provision;
    }
}
