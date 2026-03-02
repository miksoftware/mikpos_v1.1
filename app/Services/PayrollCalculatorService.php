<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\PayrollDetail;

class PayrollCalculatorService
{
    // Constantes 2026 Colombia
    const SMMLV = 1750905;
    const TRANSPORT_ALLOWANCE = 249095;
    const UVT = 49799; // UVT 2026 estimado

    // Horas mensuales legales
    const MONTHLY_HOURS = 240;

    // Tasas ARL por nivel de riesgo
    const ARL_RATES = [
        'I'   => 0.00522,
        'II'  => 0.01044,
        'III' => 0.02436,
        'IV'  => 0.04350,
        'V'   => 0.06960,
    ];

    /**
     * Calcula la nómina completa para un detalle de empleado.
     */
    public function calculate(PayrollDetail $detail, Payroll $payroll): PayrollDetail
    {
        $employee = $detail->employee;

        // Prestación de servicios no genera nómina laboral
        if ($employee->isContractor()) {
            $detail->net_pay = 0;
            return $detail;
        }

        // Colombian labor law: every month = 30 days for salary calculation
        // For monthly payrolls, always use 30 days regardless of actual calendar days
        if ($payroll->period_type === 'mensual') {
            $totalDays = 30;
        } elseif ($payroll->period_type === 'quincenal') {
            $totalDays = 15;
        } else {
            // semanal
            $totalDays = $payroll->period_start->diffInDays($payroll->period_end) + 1;
        }

        // Obtener novedades del período
        $adjustments = PayrollAdjustment::where('employee_id', $employee->id)
            ->where(function ($q) use ($payroll) {
                $q->whereBetween('start_date', [$payroll->period_start, $payroll->period_end])
                  ->orWhereBetween('end_date', [$payroll->period_start, $payroll->period_end]);
            })->get();

        $disabilityDays = 0;
        $vacationDays = 0;
        $unpaidDays = 0;

        foreach ($adjustments as $adj) {
            $start = max($adj->start_date, $payroll->period_start);
            $end = min($adj->end_date, $payroll->period_end);
            $days = $start->diffInDays($end) + 1;

            match ($adj->type) {
                'incapacidad_eps', 'incapacidad_arl', 'accidente_trabajo' => $disabilityDays += $days,
                'vacaciones' => $vacationDays += $days,
                'permiso_no_remunerado', 'ausencia_injustificada' => $unpaidDays += $days,
                default => null,
            };
        }

        $workedDays = max(0, $totalDays - $disabilityDays - $vacationDays - $unpaidDays);
        $detail->worked_days = $workedDays;

        // === DEVENGADOS ===
        // Usar salario base real (sin auxilio si está incluido)
        $realBaseSalary = (float) $employee->real_base_salary;
        $dailySalary = $realBaseSalary / 30;
        $detail->base_salary_earned = round($dailySalary * $workedDays, 2);

        // Auxilio de transporte
        $detail->transport_allowance_earned = $this->calculateTransportAllowance($employee, $workedDays, $totalDays);

        // Horas extras (ya vienen ingresadas manualmente)
        $hourlyRate = $realBaseSalary / self::MONTHLY_HOURS;
        $detail->overtime_daytime_value = $this->calculateOvertime((float) $detail->overtime_daytime_hours, 'diurna', $hourlyRate);
        $detail->overtime_nighttime_value = $this->calculateOvertime((float) $detail->overtime_nighttime_hours, 'nocturna', $hourlyRate);
        $detail->overtime_sunday_daytime_value = $this->calculateOvertime((float) $detail->overtime_sunday_daytime_hours, 'dominical_diurna', $hourlyRate);
        $detail->overtime_sunday_nighttime_value = $this->calculateOvertime((float) $detail->overtime_sunday_nighttime_hours, 'dominical_nocturna', $hourlyRate);
        $detail->night_surcharge_value = $this->calculateOvertime((float) $detail->night_surcharge_hours, 'recargo_nocturno', $hourlyRate);
        $detail->sunday_holiday_value = $this->calculateOvertime((float) $detail->sunday_holiday_hours, 'recargo_dominical', $hourlyRate);

        // Incapacidad
        $detail->disability_days = $disabilityDays;
        $detail->disability_value = $this->calculateDisability($employee, $disabilityDays, $adjustments);

        // Vacaciones
        $detail->vacation_days = $vacationDays;
        $detail->vacation_value = round($dailySalary * $vacationDays, 2);

        // Total devengado
        $detail->total_earned = round(
            (float) $detail->base_salary_earned
            + (float) $detail->transport_allowance_earned
            + (float) $detail->overtime_daytime_value
            + (float) $detail->overtime_nighttime_value
            + (float) $detail->overtime_sunday_daytime_value
            + (float) $detail->overtime_sunday_nighttime_value
            + (float) $detail->night_surcharge_value
            + (float) $detail->sunday_holiday_value
            + (float) $detail->commissions
            + (float) $detail->bonuses
            + (float) $detail->disability_value
            + (float) $detail->vacation_value
            + (float) $detail->other_income,
            2
        );

        // === IBC (Ingreso Base de Cotización) ===
        // IBC = devengado - auxilio de transporte (el auxilio NO es base para seguridad social)
        $ibc = (float) $detail->total_earned - (float) $detail->transport_allowance_earned;

        // Salario integral: IBC = 70% del salario integral
        if ($employee->isIntegralSalary()) {
            $ibc = $employee->base_salary * 0.70;
        }

        // Salario mínimo: IBC = SMMLV
        if ($employee->isMinimumWage()) {
            $ibc = max($ibc, self::SMMLV);
        }

        // IBC mínimo 1 SMMLV, máximo 25 SMMLV
        $ibc = max($ibc, self::SMMLV);
        $ibc = min($ibc, self::SMMLV * 25);

        // === DEDUCCIONES EMPLEADO ===
        $healthPension = $this->calculateHealthPension($ibc);
        $detail->health_employee = $healthPension['health_employee'];
        $detail->pension_employee = $healthPension['pension_employee'];
        $detail->solidarity_fund = $this->calculateSolidarityFund($ibc);
        $detail->income_tax_withholding = $this->calculateIncomeTaxWithholding($ibc, $employee);

        // Préstamos activos
        $detail->loan_deduction = $this->calculateLoanDeductions($employee);

        $detail->total_deductions = round(
            (float) $detail->health_employee
            + (float) $detail->pension_employee
            + (float) $detail->solidarity_fund
            + (float) $detail->income_tax_withholding
            + (float) $detail->loan_deduction
            + (float) $detail->cooperative_deduction
            + (float) $detail->libranza_deduction
            + (float) $detail->other_deductions,
            2
        );

        // === APORTES EMPLEADOR ===
        $detail->health_employer = round($ibc * 0.085, 2);
        $detail->pension_employer = round($ibc * 0.12, 2);
        $detail->arl_employer = round($ibc * (self::ARL_RATES[$employee->risk_level] ?? 0.00522), 2);

        // Parafiscales: SENA 2%, ICBF 3% (exonerados si salario < 10 SMMLV por Ley 1607/2012)
        $exoneratedParafiscales = $employee->real_base_salary < (self::SMMLV * 10);
        $detail->sena_employer = $exoneratedParafiscales ? 0 : round($ibc * 0.02, 2);
        $detail->icbf_employer = $exoneratedParafiscales ? 0 : round($ibc * 0.03, 2);
        $detail->compensation_fund_employer = round($ibc * 0.04, 2);

        // === PROVISIONES ===
        if (!$employee->isIntegralSalary()) {
            $provisionBase = (float) $detail->base_salary_earned + (float) $detail->transport_allowance_earned;
            $provisions = $this->calculateProvisions($employee, $provisionBase, (float) $detail->transport_allowance_earned);
            $detail->severance_provision = $provisions['severance'];
            $detail->severance_interest_provision = $provisions['severance_interest'];
            $detail->service_bonus_provision = $provisions['service_bonus'];
            $detail->vacation_provision = $provisions['vacation'];
        }

        // === NETO A PAGAR ===
        $detail->net_pay = round((float) $detail->total_earned - (float) $detail->total_deductions, 2);

        return $detail;
    }

    /**
     * Calcula horas extras y recargos según tipo.
     */
    public function calculateOvertime(float $hours, string $type, float $hourlyRate): float
    {
        if ($hours <= 0) return 0;

        $multiplier = match ($type) {
            'diurna' => 1.25,              // +25%
            'nocturna' => 1.75,            // +75%
            'dominical_diurna' => 2.00,    // +100% (75% dominical + 25% extra)
            'dominical_nocturna' => 2.50,  // +150% (75% dominical + 75% nocturna)
            'recargo_nocturno' => 0.35,    // 35% adicional (solo recargo, no hora completa)
            'recargo_dominical' => 0.75,   // 75% adicional (solo recargo)
            default => 1.0,
        };

        return round($hours * $hourlyRate * $multiplier, 2);
    }

    /**
     * Calcula aportes de salud y pensión (empleado y empleador).
     */
    public function calculateHealthPension(float $ibc): array
    {
        return [
            'health_employee' => round($ibc * 0.04, 2),
            'pension_employee' => round($ibc * 0.04, 2),
            'health_employer' => round($ibc * 0.085, 2),
            'pension_employer' => round($ibc * 0.12, 2),
        ];
    }

    /**
     * Fondo de solidaridad pensional: aplica para salarios > 4 SMMLV.
     * Escalonado según rangos de SMMLV.
     */
    public function calculateSolidarityFund(float $ibc): float
    {
        $smmlvCount = $ibc / self::SMMLV;

        if ($smmlvCount <= 4) return 0;

        // Base: 1% para todos > 4 SMMLV
        $rate = 0.01;

        // Adicionales escalonados
        if ($smmlvCount > 20) {
            $rate += 0.014;
        } elseif ($smmlvCount > 19) {
            $rate += 0.012;
        } elseif ($smmlvCount > 18) {
            $rate += 0.01;
        } elseif ($smmlvCount > 17) {
            $rate += 0.004;
        } elseif ($smmlvCount > 16) {
            $rate += 0.002;
        }

        return round($ibc * $rate, 2);
    }

    /**
     * Retención en la fuente simplificada (método de depuración).
     */
    public function calculateIncomeTaxWithholding(float $ibc, Employee $employee): float
    {
        // Paso 1: Ingresos laborales
        $grossIncome = $ibc;

        // Paso 2: Ingresos no constitutivos de renta (aportes obligatorios)
        $healthDeduction = $grossIncome * 0.04;
        $pensionDeduction = $grossIncome * 0.04;
        $nonTaxableIncome = $healthDeduction + $pensionDeduction;

        // Paso 3: Subtotal
        $subtotal = $grossIncome - $nonTaxableIncome;

        // Paso 4: Deducciones (dependientes 10% máx 32 UVT, intereses vivienda máx 100 UVT, medicina prepagada máx 16 UVT)
        // Simplificado: solo dependientes genérico
        $deductions = 0;

        // Paso 5: Renta exenta 25% (máximo 240 UVT mensuales)
        $rentaExenta = min($subtotal * 0.25, 240 * self::UVT);

        // Paso 6: Total deducciones + rentas exentas no puede superar 40% del subtotal
        $totalDeductions = min($deductions + $rentaExenta, $subtotal * 0.40);

        // Paso 7: Base gravable en UVT
        $taxableBase = max(0, $subtotal - $totalDeductions);
        $taxableUVT = $taxableBase / self::UVT;

        // Paso 8: Tabla de retención (Art. 383 ET)
        $withholding = $this->applyWithholdingTable($taxableUVT);

        return round($withholding * self::UVT, 2);
    }

    /**
     * Tabla de retención en la fuente Art. 383 ET (en UVT).
     */
    private function applyWithholdingTable(float $uvt): float
    {
        if ($uvt <= 95) return 0;
        if ($uvt <= 150) return ($uvt - 95) * 0.19;
        if ($uvt <= 360) return (($uvt - 150) * 0.28) + 10.45;
        if ($uvt <= 640) return (($uvt - 360) * 0.33) + 69.25;
        if ($uvt <= 945) return (($uvt - 640) * 0.35) + 161.65;
        if ($uvt <= 2300) return (($uvt - 945) * 0.37) + 268.40;
        return (($uvt - 2300) * 0.39) + 769.75;
    }

    /**
     * Provisiones mensuales.
     */
    public function calculateProvisions(Employee $employee, float $salaryBase, float $transportAllowance): array
    {
        $baseWithTransport = $salaryBase + $transportAllowance;
        $baseSalaryOnly = $salaryBase; // Vacaciones NO incluyen auxilio de transporte

        return [
            'severance' => round($baseWithTransport * 0.0833, 2),
            'severance_interest' => round($baseWithTransport * 0.0833 * 0.12 / 12, 2), // 1% mensual
            'service_bonus' => round($baseWithTransport * 0.0833, 2),
            'vacation' => round($baseSalaryOnly * 0.0417, 2),
        ];
    }

    /**
     * Auxilio de transporte: solo si salario <= 2 SMMLV.
     */
    public function calculateTransportAllowance(Employee $employee, float $workedDays, float $totalDays): float
    {
        if ($employee->isIntegralSalary()) return 0;
        if ($employee->isApprentice()) return 0;
        if (!$employee->transport_allowance) return 0;
        if ($employee->real_base_salary > (self::SMMLV * 2)) return 0;

        return round((self::TRANSPORT_ALLOWANCE / 30) * $workedDays, 2);
    }

    /**
     * Calcula valor de incapacidad.
     */
    private function calculateDisability(Employee $employee, int $days, $adjustments): float
    {
        if ($days <= 0) return 0;

        $dailySalary = $employee->real_base_salary / 30;
        $value = 0;

        foreach ($adjustments as $adj) {
            if (!in_array($adj->type, ['incapacidad_eps', 'incapacidad_arl', 'accidente_trabajo'])) continue;

            if ($adj->type === 'accidente_trabajo' || $adj->type === 'incapacidad_arl') {
                // ARL paga 100%
                $value += $dailySalary * $adj->days;
            } else {
                // EPS: días 1-2 empleador 100%, días 3+ EPS 66.67%
                $employerDays = min($adj->days, 2);
                $epsDays = max(0, $adj->days - 2);
                $value += ($dailySalary * $employerDays) + ($dailySalary * 0.6667 * $epsDays);
            }
        }

        return round($value, 2);
    }

    /**
     * Calcula deducciones por préstamos activos.
     */
    private function calculateLoanDeductions(Employee $employee): float
    {
        return (float) EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'activo')
            ->where('remaining_balance', '>', 0)
            ->sum('monthly_deduction');
    }
}
