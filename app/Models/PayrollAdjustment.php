<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    protected $fillable = [
        'employee_id', 'payroll_id', 'type',
        'start_date', 'end_date', 'days', 'description', 'supporting_document',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'incapacidad_eps' => 'Incapacidad EPS',
            'incapacidad_arl' => 'Incapacidad ARL',
            'accidente_trabajo' => 'Accidente de Trabajo',
            'vacaciones' => 'Vacaciones',
            'permiso_remunerado' => 'Permiso Remunerado',
            'permiso_no_remunerado' => 'Permiso No Remunerado',
            'licencia_maternidad' => 'Licencia de Maternidad',
            'licencia_paternidad' => 'Licencia de Paternidad',
            'ausencia_injustificada' => 'Ausencia Injustificada',
            default => $this->type,
        };
    }
}
