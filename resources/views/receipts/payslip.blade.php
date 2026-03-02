<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Desprendible de Nómina - {{ $detail->employee->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: letter; margin: 15mm; }
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1e293b; background: #f1f5f9; }
        .payslip { max-width: 700px; margin: 20px auto; background: white; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #ff7261, #a855f7); color: white; padding: 20px 24px; }
        .header h1 { font-size: 18px; font-weight: 700; }
        .header p { font-size: 12px; opacity: 0.9; margin-top: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 16px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .info-item { font-size: 11px; }
        .info-item .label { color: #64748b; }
        .info-item .value { font-weight: 600; color: #1e293b; }
        .section { padding: 12px 24px; }
        .section-title { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 2px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        table th { text-align: left; padding: 6px 8px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 10px; text-transform: uppercase; }
        table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
        table td.right, table th.right { text-align: right; }
        .total-row td { font-weight: 700; border-top: 2px solid #e2e8f0; background: #f8fafc; }
        .net-pay { padding: 16px 24px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border-top: 2px solid #22c55e; }
        .net-pay .label { font-size: 14px; font-weight: 700; color: #166534; }
        .net-pay .amount { font-size: 24px; font-weight: 800; color: #15803d; }
        .employer-section { background: #fefce8; border-top: 1px solid #fde68a; padding: 12px 24px; }
        .footer { padding: 20px 24px; border-top: 1px solid #e2e8f0; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; }
        .sig-line { border-top: 1px solid #94a3b8; padding-top: 4px; text-align: center; font-size: 10px; color: #64748b; }
        .print-btn { display: block; margin: 20px auto; padding: 10px 32px; background: linear-gradient(135deg, #ff7261, #a855f7); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .print-btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Imprimir Desprendible</button>

    @php
        $employee = $detail->employee;
        $payroll = $detail->payroll;
        $totalExtras = (float)$detail->overtime_daytime_value + (float)$detail->overtime_nighttime_value + (float)$detail->overtime_sunday_daytime_value + (float)$detail->overtime_sunday_nighttime_value + (float)$detail->night_surcharge_value + (float)$detail->sunday_holiday_value;
        $totalEmployerCost = (float)$detail->health_employer + (float)$detail->pension_employer + (float)$detail->arl_employer + (float)$detail->sena_employer + (float)$detail->icbf_employer + (float)$detail->compensation_fund_employer;
        $totalProvisions = (float)$detail->severance_provision + (float)$detail->severance_interest_provision + (float)$detail->service_bonus_provision + (float)$detail->vacation_provision;
    @endphp

    <div class="payslip">
        <div class="header">
            <h1>Desprendible de Nómina</h1>
            <p>{{ $employee->branch?->name ?? 'MikPOS' }} — Período: {{ $payroll->period_label }} ({{ ucfirst($payroll->period_type) }})</p>
        </div>

        <div class="info-grid">
            <div class="info-item"><span class="label">Empleado:</span> <span class="value">{{ $employee->full_name }}</span></div>
            <div class="info-item"><span class="label">Documento:</span> <span class="value">{{ $employee->document_type }} {{ $employee->document_number }}</span></div>
            <div class="info-item"><span class="label">Cargo:</span> <span class="value">{{ $employee->position }}</span></div>
            <div class="info-item"><span class="label">Contrato:</span> <span class="value">{{ ucfirst(str_replace('_', ' ', $employee->contract_type)) }}</span></div>
            <div class="info-item"><span class="label">Salario Base:</span> <span class="value">${{ number_format($employee->base_salary, 0, ',', '.') }}</span></div>
            <div class="info-item"><span class="label">Días Trabajados:</span> <span class="value">{{ number_format($detail->worked_days, 0) }}</span></div>
        </div>

        <!-- Devengados -->
        <div class="section">
            <div class="section-title">Devengados</div>
            <table>
                <thead><tr><th>Concepto</th><th class="right">Valor</th></tr></thead>
                <tbody>
                    <tr><td>Salario</td><td class="right">${{ number_format($detail->base_salary_earned, 0, ',', '.') }}</td></tr>
                    @if((float)$detail->transport_allowance_earned > 0)
                    <tr><td>Auxilio de Transporte</td><td class="right">${{ number_format($detail->transport_allowance_earned, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->overtime_daytime_value > 0)
                    <tr><td>H.E. Diurnas ({{ $detail->overtime_daytime_hours }}h)</td><td class="right">${{ number_format($detail->overtime_daytime_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->overtime_nighttime_value > 0)
                    <tr><td>H.E. Nocturnas ({{ $detail->overtime_nighttime_hours }}h)</td><td class="right">${{ number_format($detail->overtime_nighttime_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->overtime_sunday_daytime_value > 0)
                    <tr><td>H.E. Dom. Diurnas ({{ $detail->overtime_sunday_daytime_hours }}h)</td><td class="right">${{ number_format($detail->overtime_sunday_daytime_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->overtime_sunday_nighttime_value > 0)
                    <tr><td>H.E. Dom. Nocturnas ({{ $detail->overtime_sunday_nighttime_hours }}h)</td><td class="right">${{ number_format($detail->overtime_sunday_nighttime_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->night_surcharge_value > 0)
                    <tr><td>Recargo Nocturno ({{ $detail->night_surcharge_hours }}h)</td><td class="right">${{ number_format($detail->night_surcharge_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->sunday_holiday_value > 0)
                    <tr><td>Recargo Dom/Fest. ({{ $detail->sunday_holiday_hours }}h)</td><td class="right">${{ number_format($detail->sunday_holiday_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->commissions > 0)
                    <tr><td>Comisiones</td><td class="right">${{ number_format($detail->commissions, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->bonuses > 0)
                    <tr><td>Bonificaciones</td><td class="right">${{ number_format($detail->bonuses, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->disability_value > 0)
                    <tr><td>Incapacidad ({{ $detail->disability_days }} días)</td><td class="right">${{ number_format($detail->disability_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->vacation_value > 0)
                    <tr><td>Vacaciones ({{ $detail->vacation_days }} días)</td><td class="right">${{ number_format($detail->vacation_value, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->other_income > 0)
                    <tr><td>Otros Ingresos</td><td class="right">${{ number_format($detail->other_income, 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="total-row"><td>Total Devengado</td><td class="right">${{ number_format($detail->total_earned, 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Deducciones -->
        <div class="section">
            <div class="section-title">Deducciones</div>
            <table>
                <thead><tr><th>Concepto</th><th class="right">Valor</th></tr></thead>
                <tbody>
                    <tr><td>Salud (4%)</td><td class="right">${{ number_format($detail->health_employee, 0, ',', '.') }}</td></tr>
                    <tr><td>Pensión (4%)</td><td class="right">${{ number_format($detail->pension_employee, 0, ',', '.') }}</td></tr>
                    @if((float)$detail->solidarity_fund > 0)
                    <tr><td>Fondo de Solidaridad</td><td class="right">${{ number_format($detail->solidarity_fund, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->income_tax_withholding > 0)
                    <tr><td>Retención en la Fuente</td><td class="right">${{ number_format($detail->income_tax_withholding, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->loan_deduction > 0)
                    <tr><td>Préstamos</td><td class="right">${{ number_format($detail->loan_deduction, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->cooperative_deduction > 0)
                    <tr><td>Cooperativa</td><td class="right">${{ number_format($detail->cooperative_deduction, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->libranza_deduction > 0)
                    <tr><td>Libranza</td><td class="right">${{ number_format($detail->libranza_deduction, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->other_deductions > 0)
                    <tr><td>Otras Deducciones</td><td class="right">${{ number_format($detail->other_deductions, 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="total-row"><td>Total Deducciones</td><td class="right">${{ number_format($detail->total_deductions, 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Neto a Pagar -->
        <div class="net-pay">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="label">NETO A PAGAR</span>
                <span class="amount">${{ number_format($detail->net_pay, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Aportes Empleador (informativo) -->
        <div class="employer-section">
            <div class="section-title" style="color: #92400e; border-color: #fde68a;">Aportes del Empleador (Informativo)</div>
            <table>
                <tbody>
                    <tr><td>Salud Empleador (8.5%)</td><td class="right">${{ number_format($detail->health_employer, 0, ',', '.') }}</td></tr>
                    <tr><td>Pensión Empleador (12%)</td><td class="right">${{ number_format($detail->pension_employer, 0, ',', '.') }}</td></tr>
                    <tr><td>ARL</td><td class="right">${{ number_format($detail->arl_employer, 0, ',', '.') }}</td></tr>
                    @if((float)$detail->sena_employer > 0)
                    <tr><td>SENA (2%)</td><td class="right">${{ number_format($detail->sena_employer, 0, ',', '.') }}</td></tr>
                    @endif
                    @if((float)$detail->icbf_employer > 0)
                    <tr><td>ICBF (3%)</td><td class="right">${{ number_format($detail->icbf_employer, 0, ',', '.') }}</td></tr>
                    @endif
                    <tr><td>Caja de Compensación (4%)</td><td class="right">${{ number_format($detail->compensation_fund_employer, 0, ',', '.') }}</td></tr>
                    <tr class="total-row"><td>Total Aportes Empleador</td><td class="right">${{ number_format($totalEmployerCost, 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </div>

        @if($totalProvisions > 0)
        <div class="section" style="background: #faf5ff;">
            <div class="section-title" style="color: #7e22ce; border-color: #e9d5ff;">Provisiones del Período</div>
            <table>
                <tbody>
                    <tr><td>Cesantías (8.33%)</td><td class="right">${{ number_format($detail->severance_provision, 0, ',', '.') }}</td></tr>
                    <tr><td>Intereses Cesantías (1%)</td><td class="right">${{ number_format($detail->severance_interest_provision, 0, ',', '.') }}</td></tr>
                    <tr><td>Prima de Servicios (8.33%)</td><td class="right">${{ number_format($detail->service_bonus_provision, 0, ',', '.') }}</td></tr>
                    <tr><td>Vacaciones (4.17%)</td><td class="right">${{ number_format($detail->vacation_provision, 0, ',', '.') }}</td></tr>
                    <tr class="total-row"><td>Total Provisiones</td><td class="right">${{ number_format($totalProvisions, 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </div>
        @endif

        <div class="footer">
            <p style="font-size: 10px; color: #94a3b8; text-align: center;">Costo total empleado para la empresa: ${{ number_format((float)$detail->net_pay + (float)$detail->total_deductions + $totalEmployerCost + $totalProvisions, 0, ',', '.') }}</p>
            <div class="signatures">
                <div class="sig-line">Empleador</div>
                <div class="sig-line">Empleado: {{ $employee->full_name }}</div>
            </div>
        </div>
    </div>
</body>
</html>
