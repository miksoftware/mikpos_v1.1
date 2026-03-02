<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'branch_id', 'first_name', 'last_name',
        'document_type', 'document_number', 'email', 'phone', 'address', 'birth_date',
        'hire_date', 'position', 'department', 'contract_type', 'salary_type',
        'base_salary', 'transport_allowance', 'transport_included_in_salary', 'risk_level',
        'payment_frequency',
        'health_fund', 'pension_fund', 'severance_fund', 'compensation_fund',
        'bank_name', 'bank_account_type', 'bank_account_number',
        'status', 'termination_date', 'termination_reason',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'base_salary' => 'decimal:2',
            'transport_allowance' => 'boolean',
            'transport_included_in_salary' => 'boolean',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payrollDetails(): HasMany
    {
        return $this->hasMany(PayrollDetail::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'activo');
    }

    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        return $branchId ? $query->where('employees.branch_id', $branchId) : $query;
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->full_name} ({$this->document_number})";
    }

    public function isIntegralSalary(): bool
    {
        return $this->salary_type === 'integral';
    }

    public function isMinimumWage(): bool
    {
        return $this->salary_type === 'minimo';
    }

    /**
     * Salario base real para cálculos.
     * Si el auxilio está incluido en el salario, se resta para obtener la base real.
     */
    public function getRealBaseSalaryAttribute(): float
    {
        if ($this->transport_included_in_salary && $this->transport_allowance) {
            return (float) $this->base_salary - \App\Services\PayrollCalculatorService::TRANSPORT_ALLOWANCE;
        }
        return (float) $this->base_salary;
    }

    public function isApprentice(): bool
    {
        return $this->contract_type === 'aprendizaje';
    }

    public function isContractor(): bool
    {
        return $this->contract_type === 'prestacion_servicios';
    }
}
