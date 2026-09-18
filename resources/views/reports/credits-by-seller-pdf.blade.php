<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Créditos por Vendedor</title>
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
            font-size: 8.5px;
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
        .header-brand {
            vertical-align: top;
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

        /* Seller Card */
        .seller-card {
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .seller-header {
            background: #f8fafc;
            padding: 5px 8px;
            border-bottom: 1px solid #cbd5e1;
        }
        .seller-header table {
            width: 100%;
            border-collapse: collapse;
        }
        .seller-name {
            font-size: 9.5px;
            font-weight: bold;
            color: #1e293b;
        }
        .seller-meta {
            font-size: 7px;
            color: #64748b;
            margin-top: 1px;
        }
        .seller-totals {
            text-align: right;
            font-size: 7.5px;
        }
        .seller-totals span {
            margin-left: 6px;
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
            padding: 4px 6px;
            border: 1px solid #334155;
            text-align: left;
        }
        .invoice-table th.text-center { text-align: center; }
        .invoice-table th.text-right { text-align: right; }
        .invoice-table td {
            padding: 4px 6px;
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

        /* Badges */
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
            background: #f8fafc !important;
            font-weight: bold;
            font-size: 7.5px;
            color: #1e293b;
            border-top: 1px solid #cbd5e1;
            padding: 4px 6px;
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
                    <h1>Reporte de Créditos por Vendedor</h1>
                    <p class="tagline">Análisis de cartera y cobranza distribuida por vendedor</p>
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
                <div class="kpi-title">Vendedores con Cartera</div>
                <div class="kpi-value">{{ number_format(count($sellersSummaries)) }}</div>
                <div class="kpi-subtitle">{{ number_format($grandTotalInvoices) }} factura(s) a crédito</div>
            </td>
            <td class="kpi-cell kpi-primary" style="width: 25%;">
                <div class="kpi-title">Total Créditos</div>
                <div class="kpi-value text-primary">${{ number_format($grandTotalCredit, 2) }}</div>
                <div class="kpi-subtitle">Monto total financiado</div>
            </td>
            <td class="kpi-cell kpi-success" style="width: 25%;">
                <div class="kpi-title">Total Cobrado</div>
                <div class="kpi-value text-success">${{ number_format($grandTotalPaid, 2) }}</div>
                <div class="kpi-subtitle">
                    {{ $grandTotalCredit > 0 ? number_format(($grandTotalPaid / $grandTotalCredit) * 100, 1) : 0 }}% recuperado
                </div>
            </td>
            <td class="kpi-cell kpi-danger" style="width: 25%;">
                <div class="kpi-title">Total por Cobrar</div>
                <div class="kpi-value text-danger">${{ number_format($grandTotalRemaining, 2) }}</div>
                <div class="kpi-subtitle">Saldo pendiente de cobro</div>
            </td>
        </tr>
    </table>

    {{-- Sellers Detail --}}
    @forelse($sellersSummaries as $seller)
        @php
            $invoices = $invoicesBySeller->get($seller->seller_id ?? 0, collect());
            $sellerTotalCredit = (float) $seller->total_credit;
            $sellerTotalPaid = (float) $seller->total_paid;
            $sellerTotalRemaining = (float) $seller->total_remaining;
        @endphp

        <div class="seller-card">
            <div class="seller-header">
                <table>
                    <tr>
                        <td style="width: 50%;">
                            <span class="seller-name">{{ $seller->seller_name }}</span>
                            <div class="seller-meta">
                                {{ $seller->seller_email ?? 'Cartera de cobranza' }}
                            </div>
                        </td>
                        <td class="seller-totals" style="width: 50%;">
                            <span><strong>Facturas:</strong> {{ $seller->total_invoices }}</span>
                            <span><strong>Total:</strong> ${{ number_format($sellerTotalCredit, 2) }}</span>
                            <span><strong>Cobrado:</strong> <span style="color: #059669;">${{ number_format($sellerTotalPaid, 2) }}</span></span>
                            <span><strong>Por Cobrar:</strong> <span style="color: #dc2626; font-weight: bold;">${{ number_format($sellerTotalRemaining, 2) }}</span></span>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Factura</th>
                        <th style="width: 25%;">Cliente</th>
                        <th style="width: 14%;">Fecha</th>
                        <th class="text-center" style="width: 14%;">Días en Mora</th>
                        <th class="text-right" style="width: 16%;">Total Crédito</th>
                        <th class="text-right" style="width: 16%;">Por Cobrar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                        @php
                            $rem = (float) $inv->credit_amount - (float) $inv->paid_amount;
                            $daysOverdue = $inv->days_overdue;
                        @endphp
                        <tr>
                            <td class="font-bold">{{ $inv->invoice_number }}</td>
                            <td>{{ $inv->customer ? ($inv->customer->business_name ?: $inv->customer->first_name . ' ' . $inv->customer->last_name) : 'Cliente General' }}</td>
                            <td>{{ $inv->created_at->format('d/m/Y') }}</td>
                            <td class="text-center">
                                @if($inv->payment_status === 'paid' || $rem <= 0)
                                    <span class="badge badge-settled">Saldado</span>
                                @elseif($daysOverdue === 0)
                                    <span class="badge badge-ontime">Al día</span>
                                @elseif($daysOverdue <= 30)
                                    <span class="badge badge-mora-low">{{ $daysOverdue }} días</span>
                                @else
                                    <span class="badge badge-mora-high">{{ $daysOverdue }} días</span>
                                @endif
                            </td>
                            <td class="text-right font-bold">${{ number_format($inv->credit_amount, 2) }}</td>
                            <td class="text-right font-bold" style="color: {{ $rem > 0 ? '#dc2626' : '#64748b' }};">
                                ${{ number_format($rem, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="4" style="text-align: right; text-transform: uppercase;">
                            Subtotal {{ $seller->seller_name }} ({{ $seller->total_invoices }} fac.):
                        </td>
                        <td class="text-right">${{ number_format($sellerTotalCredit, 2) }}</td>
                        <td class="text-right" style="color: #dc2626; font-weight: bold;">${{ number_format($sellerTotalRemaining, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <div class="empty-state">
            No se encontraron registros de créditos por vendedor para los filtros seleccionados.
        </div>
    @endforelse

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->page_text(450, 810, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 7.5, array(0.58, 0.64, 0.72));
            $pdf->page_text(30, 810, "MikPOS · Reporte Oficial de Cartera y Créditos", $font, 7.5, array(0.58, 0.64, 0.72));
        }
    </script>
</body>
</html>
