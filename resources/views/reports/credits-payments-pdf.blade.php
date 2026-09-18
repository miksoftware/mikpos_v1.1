<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Historial de Abonos y Pagos</title>
    <style>
        @page {
            margin: 25px 30px 45px 30px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            line-height: 1.35;
            color: #1e293b;
            background-color: #ffffff;
        }
        .header-container {
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-brand h1 {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 2px;
        }
        .header-brand .tagline {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 500;
        }
        .header-brand .branch-badge {
            display: inline-block;
            margin-top: 4px;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #e2e8f0;
        }
        .header-meta {
            text-align: right;
            vertical-align: top;
            font-size: 8px;
            color: #64748b;
        }
        .header-meta table {
            margin-left: auto;
            border-collapse: collapse;
        }
        .header-meta td {
            padding: 1.5px 4px;
        }
        .header-meta td.label {
            font-weight: bold;
            color: #475569;
            text-align: right;
        }
        .header-meta td.value {
            text-align: left;
            color: #1e293b;
        }

        /* KPI Summary */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 6px;
            margin-bottom: 12px;
        }
        .kpi-cell {
            padding: 7px 9px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .kpi-cell.kpi-primary {
            border-left: 3px solid #6366f1;
            background: #faf5ff;
        }
        .kpi-cell.kpi-success {
            border-left: 3px solid #10b981;
            background: #f0fdf4;
        }
        .kpi-cell.kpi-danger {
            border-left: 3px solid #ef4444;
            background: #fef2f2;
        }
        .kpi-title {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 1px;
        }
        .kpi-value.text-success { color: #059669; }
        .kpi-value.text-danger { color: #dc2626; }
        .kpi-value.text-primary { color: #4f46e5; }
        .kpi-subtitle {
            font-size: 6.5px;
            color: #94a3b8;
        }

        /* Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        .invoice-table th {
            background: #334155;
            color: #ffffff;
            font-weight: 700;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 5px 6px;
            border: 1px solid #334155;
            text-align: left;
        }
        .invoice-table th.text-center { text-align: center; }
        .invoice-table th.text-right { text-align: right; }
        .invoice-table td {
            padding: 4.5px 6px;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }
        .invoice-table tr:nth-child(even) td {
            background: #fafbfd;
        }
        .invoice-table td.text-center { text-align: center; }
        .invoice-table td.text-right { text-align: right; }
        .invoice-table td.font-bold { font-weight: bold; }

        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
        }
        .badge-payable { background: #fee2e2; color: #b91c1c; }
        .badge-receivable { background: #dbeafe; color: #1d4ed8; }

        .subtotal-row td {
            background: #0f172a !important;
            font-weight: bold;
            font-size: 8px;
            color: #ffffff;
            padding: 6px 8px;
        }

        .empty-state {
            text-align: center;
            padding: 30px 15px;
            color: #94a3b8;
            font-size: 10px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="header-brand" style="width: 55%;">
                    <h1>Reporte de Abonos y Pagos</h1>
                    <p class="tagline">Historial detallado de abonos recibidos y pagos efectuados</p>
                    <div class="branch-badge">
                        Sucursal: {{ $branchName }}
                    </div>
                </td>
                <td class="header-meta" style="width: 45%;">
                    <table>
                        <tr>
                            <td class="label">Período:</td>
                            <td class="value">{{ $startDate && $endDate ? "$startDate al $endDate" : 'Histórico Completo' }}</td>
                        </tr>
                        @if($search)
                        <tr>
                            <td class="label">Búsqueda:</td>
                            <td class="value">"{{ $search }}"</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="label">Fecha Emisión:</td>
                            <td class="value">{{ $generatedAt }}</td>
                        </tr>
                        <tr>
                            <td class="label">Generado por:</td>
                            <td class="value">{{ $generatedBy }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- KPI Cards --}}
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell" style="width: 33.3%;">
                <div class="kpi-title">Total Abonos / Pagos</div>
                <div class="kpi-value">{{ number_format(count($payments)) }}</div>
                <div class="kpi-subtitle">Movimientos registrados</div>
            </td>
            <td class="kpi-cell kpi-success" style="width: 33.3%;">
                <div class="kpi-title">Total Cobrado (Clientes)</div>
                <div class="kpi-value text-success">${{ number_format($totalReceivable, 2) }}</div>
                <div class="kpi-subtitle">Ingresos por cartera</div>
            </td>
            <td class="kpi-cell kpi-danger" style="width: 33.4%;">
                <div class="kpi-title">Total Pagado (Proveedores)</div>
                <div class="kpi-value text-danger">${{ number_format($totalPayable, 2) }}</div>
                <div class="kpi-subtitle">Egresos a proveedores</div>
            </td>
        </tr>
    </table>

    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 14%;">Nº Pago</th>
                <th style="width: 12%;">Tipo</th>
                <th style="width: 26%;">Entidad</th>
                <th style="width: 16%;">Método de Pago</th>
                <th style="width: 14%;">Fecha</th>
                <th class="text-right" style="width: 18%;">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                @php
                    $entityName = $p->credit_type === 'payable'
                        ? ($p->purchase?->supplier?->name ?? '-')
                        : ($p->sale?->customer ? ($p->sale->customer->business_name ?: $p->sale->customer->first_name . ' ' . $p->sale->customer->last_name) : '-');
                @endphp
                <tr>
                    <td class="font-bold">{{ $p->payment_number }}</td>
                    <td>
                        @if($p->credit_type === 'payable')
                            <span class="badge badge-payable">Pago Prov.</span>
                        @else
                            <span class="badge badge-receivable">Cobro Cli.</span>
                        @endif
                    </td>
                    <td><strong>{{ $entityName }}</strong></td>
                    <td>{{ $p->paymentMethod?->name ?? 'General' }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="text-right font-bold" style="color: {{ $p->credit_type === 'payable' ? '#dc2626' : '#059669' }};">
                        ${{ number_format($p->amount, 2) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">No se registraron pagos o abonos para los filtros seleccionados</td></tr>
            @endforelse
        </tbody>
        @if(count($payments) > 0)
        <tfoot>
            <tr class="subtotal-row">
                <td colspan="5" style="text-align: right; text-transform: uppercase;">Total Transacciones ({{ count($payments) }} abonos):</td>
                <td class="text-right">${{ number_format($totalReceivable + $totalPayable, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->page_text(450, 810, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 7.5, array(0.58, 0.64, 0.72));
            $pdf->page_text(30, 810, "MikPOS · Reporte Oficial de Cartera y Créditos", $font, 7.5, array(0.58, 0.64, 0.72));
        }
    </script>
</body>
</html>
