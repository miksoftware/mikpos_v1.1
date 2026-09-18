<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Detallado de Créditos por Cliente</title>
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
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fee2e2; color: #b91c1c; }
        .badge-ontime { background: #ecfdf5; color: #047857; border: 0.5px solid #a7f3d0; }
        .badge-mora-low { background: #fffbeb; color: #b45309; border: 0.5px solid #fde68a; }
        .badge-mora-high { background: #fef2f2; color: #b91c1c; font-weight: 800; border: 0.5px solid #fca5a5; }
        .badge-settled { background: #f1f5f9; color: #64748b; }

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
                    <h1>Reporte de Créditos por Cliente</h1>
                    <p class="tagline">Detalle individual de facturas a crédito por cliente</p>
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
                        <tr>
                            <td class="label">Estado Filtro:</td>
                            <td class="value">
                                @if($paymentStatus === 'pending') Solo Pendientes
                                @elseif($paymentStatus === 'paid') Solo Pagados
                                @else Todos los Estados
                                @endif
                            </td>
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
            <td class="kpi-cell" style="width: 25%;">
                <div class="kpi-title">Facturas Registradas</div>
                <div class="kpi-value">{{ number_format(count($items)) }}</div>
                <div class="kpi-subtitle">Total transacciones</div>
            </td>
            <td class="kpi-cell kpi-primary" style="width: 25%;">
                <div class="kpi-title">Total Créditos</div>
                <div class="kpi-value text-primary">${{ number_format($totalCredit, 2) }}</div>
                <div class="kpi-subtitle">Monto total financiado</div>
            </td>
            <td class="kpi-cell kpi-success" style="width: 25%;">
                <div class="kpi-title">Total Cobrado</div>
                <div class="kpi-value text-success">${{ number_format($totalPaid, 2) }}</div>
                <div class="kpi-subtitle">
                    {{ $totalCredit > 0 ? number_format(($totalPaid / $totalCredit) * 100, 1) : 0 }}% recuperado
                </div>
            </td>
            <td class="kpi-cell kpi-danger" style="width: 25%;">
                <div class="kpi-title">Saldo Pendiente</div>
                <div class="kpi-value text-danger">${{ number_format($totalRemaining, 2) }}</div>
                <div class="kpi-subtitle">Por cobrar</div>
            </td>
        </tr>
    </table>

    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 14%;">Factura</th>
                <th style="width: 26%;">Cliente</th>
                <th style="width: 12%;">Fecha</th>
                <th class="text-center" style="width: 14%;">Días en Mora</th>
                <th class="text-right" style="width: 17%;">Total Crédito</th>
                <th class="text-right" style="width: 17%;">Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $rem = (float) $item->credit_amount - (float) $item->paid_amount;
                    $daysOverdue = $item->days_overdue;
                @endphp
                <tr>
                    <td class="font-bold">{{ $item->invoice_number }}</td>
                    <td>
                        <strong>{{ $item->customer_name }}</strong>
                        @if($item->document_number)<div style="font-size: 6.5px; color: #64748b;">Doc: {{ $item->document_number }}</div>@endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}</td>
                    <td class="text-center">
                        @if($item->payment_status === 'paid' || $rem <= 0)
                            <span class="badge badge-settled">Saldado</span>
                        @elseif($daysOverdue === 0)
                            <span class="badge badge-ontime">Al día</span>
                        @elseif($daysOverdue <= 30)
                            <span class="badge badge-mora-low">{{ $daysOverdue }} días</span>
                        @else
                            <span class="badge badge-mora-high">{{ $daysOverdue }} días</span>
                        @endif
                    </td>
                    <td class="text-right font-bold">${{ number_format($item->credit_amount, 2) }}</td>
                    <td class="text-right font-bold" style="color: {{ $rem > 0 ? '#dc2626' : '#64748b' }};">
                        ${{ number_format($rem, 2) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">No se encontraron créditos registrados</td></tr>
            @endforelse
        </tbody>
        @if(count($items) > 0)
        <tfoot>
            <tr class="subtotal-row">
                <td colspan="4" style="text-align: right; text-transform: uppercase;">Total General ({{ count($items) }} facturas):</td>
                <td class="text-right">${{ number_format($totalCredit, 2) }}</td>
                <td class="text-right" style="color: #f87171;">${{ number_format($totalRemaining, 2) }}</td>
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
