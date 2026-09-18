<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Cronológico de Créditos</title>
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
        .badge-purchase { background: #fee2e2; color: #b91c1c; }
        .badge-sale { background: #dbeafe; color: #1d4ed8; }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fee2e2; color: #b91c1c; }

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
                    <h1>Reporte Cronológico de Créditos</h1>
                    <p class="tagline">Movimiento de cuentas por cobrar y por pagar por fecha</p>
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
                <div class="kpi-title">Total Registros</div>
                <div class="kpi-value">{{ number_format(count($items)) }}</div>
                <div class="kpi-subtitle">Créditos analizados</div>
            </td>
            <td class="kpi-cell kpi-primary" style="width: 25%;">
                <div class="kpi-title">Monto Total</div>
                <div class="kpi-value text-primary">${{ number_format($totalCredit, 2) }}</div>
                <div class="kpi-subtitle">Créditos acumulados</div>
            </td>
            <td class="kpi-cell kpi-success" style="width: 25%;">
                <div class="kpi-title">Monto Pagado</div>
                <div class="kpi-value text-success">${{ number_format($totalPaid, 2) }}</div>
                <div class="kpi-subtitle">Total cancelado</div>
            </td>
            <td class="kpi-cell kpi-danger" style="width: 25%;">
                <div class="kpi-title">Saldo Pendiente</div>
                <div class="kpi-value text-danger">${{ number_format($totalRemaining, 2) }}</div>
                <div class="kpi-subtitle">Total saldo vivo</div>
            </td>
        </tr>
    </table>

    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 12%;">Tipo</th>
                <th style="width: 14%;">Documento</th>
                <th style="width: 26%;">Entidad</th>
                <th style="width: 12%;">Fecha</th>
                <th class="text-right" style="width: 18%;">Total</th>
                <th class="text-right" style="width: 18%;">Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php $rem = (float) $item->credit_amount - (float) $item->paid_amount; @endphp
                <tr>
                    <td>
                        @if($item->record_type === 'purchase')
                            <span class="badge badge-purchase">Por Pagar</span>
                        @else
                            <span class="badge badge-sale">Por Cobrar</span>
                        @endif
                    </td>
                    <td class="font-bold">{{ $item->doc_number }}</td>
                    <td>{{ $item->entity_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}</td>
                    <td class="text-right font-bold">${{ number_format($item->credit_amount, 2) }}</td>
                    <td class="text-right font-bold" style="color: {{ $item->record_type === 'purchase' ? '#dc2626' : '#2563eb' }};">
                        ${{ number_format($rem, 2) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">No se encontraron registros para los filtros aplicados</td></tr>
            @endforelse
        </tbody>
        @if(count($items) > 0)
        <tfoot>
            <tr class="subtotal-row">
                <td colspan="4" style="text-align: right; text-transform: uppercase;">Total Consolidado ({{ count($items) }} reg.):</td>
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
