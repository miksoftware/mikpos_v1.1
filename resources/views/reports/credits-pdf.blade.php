<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Cartera y Créditos</title>
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

        /* Header */
        .header-container {
            width: 100%;
            margin-bottom: 18px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-brand {
            vertical-align: top;
        }
        .header-brand h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 2px;
        }
        .header-brand .tagline {
            font-size: 10px;
            color: #64748b;
            font-weight: 500;
        }
        .header-brand .branch-badge {
            display: inline-block;
            margin-top: 5px;
            background: #f1f5f9;
            color: #475569;
            padding: 2px 8px;
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

        /* KPI Summary Grid */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 18px;
        }
        .kpi-cell {
            padding: 8px 10px;
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
        .kpi-cell.kpi-warning {
            border-left: 3px solid #f59e0b;
            background: #fffbeb;
        }
        .kpi-title {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .kpi-value.text-success { color: #059669; }
        .kpi-value.text-danger { color: #dc2626; }
        .kpi-value.text-primary { color: #4f46e5; }
        .kpi-value.text-warning { color: #d97706; }
        .kpi-subtitle {
            font-size: 7px;
            color: #94a3b8;
        }

        /* Customer Block */
        .customer-card {
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .customer-header {
            background: #f1f5f9;
            padding: 6px 10px;
            border-bottom: 1px solid #cbd5e1;
        }
        .customer-header table {
            width: 100%;
            border-collapse: collapse;
        }
        .customer-name {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
        }
        .customer-meta {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 1px;
        }
        .customer-totals {
            text-align: right;
            font-size: 8px;
        }
        .customer-totals span {
            margin-left: 8px;
        }

        /* Invoice Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
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

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-partial { background: #fef3c7; color: #b45309; }
        .badge-pending { background: #fee2e2; color: #b91c1c; }
        .badge-ontime { background: #ecfdf5; color: #047857; border: 0.5px solid #a7f3d0; }
        .badge-mora-low { background: #fffbeb; color: #b45309; border: 0.5px solid #fde68a; }
        .badge-mora-mid { background: #ffedd5; color: #c2410c; border: 0.5px solid #fed7aa; }
        .badge-mora-high { background: #fef2f2; color: #b91c1c; font-weight: 800; border: 0.5px solid #fca5a5; }
        .badge-settled { background: #f1f5f9; color: #64748b; }

        /* Subtotal Row */
        .subtotal-row td {
            background: #f8fafc !important;
            font-weight: bold;
            font-size: 7.5px;
            color: #1e293b;
            border-top: 1px solid #cbd5e1;
            padding: 5px 6px;
        }

        /* Grand Total Section */
        .grand-total-box {
            margin-top: 15px;
            padding: 10px 14px;
            background: #0f172a;
            color: #ffffff;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .grand-total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grand-total-table td {
            vertical-align: middle;
        }
        .grand-total-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ffffff;
        }
        .grand-total-desc {
            font-size: 7.5px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .grand-total-stat {
            text-align: right;
            padding-left: 15px;
        }
        .grand-total-stat .lbl {
            font-size: 7px;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .grand-total-stat .val {
            font-size: 12px;
            font-weight: 800;
            color: #ffffff;
        }
        .grand-total-stat .val.green { color: #4ade80; }
        .grand-total-stat .val.red { color: #f87171; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
            font-size: 11px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px dashed #cbd5e1;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 7px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="header-brand" style="width: 55%;">
                    <h1>Reporte de Cartera y Créditos</h1>
                    <p class="tagline">Análisis detallado de cuentas por cobrar, vencimientos y días en mora</p>
                    <div class="branch-badge">
                        Sucursal: {{ $branchName }}
                        @if($seller) · Vendedor: {{ $seller->name }} @endif
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
                                @elseif($paymentStatus === 'partial') Solo Parciales
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
            <td class="kpi-cell" style="width: 20%;">
                <div class="kpi-title">Clientes con Cartera</div>
                <div class="kpi-value">{{ number_format(count($customerSummaries)) }}</div>
                <div class="kpi-subtitle">{{ number_format($grandTotalInvoices) }} factura(s) a crédito</div>
            </td>
            <td class="kpi-cell kpi-primary" style="width: 20%;">
                <div class="kpi-title">Total Créditos Otorgados</div>
                <div class="kpi-value text-primary">${{ number_format($grandTotalCredit, 2) }}</div>
                <div class="kpi-subtitle">Monto total financiado</div>
            </td>
            <td class="kpi-cell kpi-success" style="width: 20%;">
                <div class="kpi-title">Total Cobrado</div>
                <div class="kpi-value text-success">${{ number_format($grandTotalPaid, 2) }}</div>
                <div class="kpi-subtitle">
                    {{ $grandTotalCredit > 0 ? number_format(($grandTotalPaid / $grandTotalCredit) * 100, 1) : 0 }}% recuperado
                </div>
            </td>
            <td class="kpi-cell kpi-danger" style="width: 20%;">
                <div class="kpi-title">Saldo Pendiente de Cobro</div>
                <div class="kpi-value text-danger">${{ number_format($grandTotalRemaining, 2) }}</div>
                <div class="kpi-subtitle">
                    {{ $grandTotalCredit > 0 ? number_format(($grandTotalRemaining / $grandTotalCredit) * 100, 1) : 0 }}% por cobrar
                </div>
            </td>
            <td class="kpi-cell kpi-warning" style="width: 20%;">
                <div class="kpi-title">Cartera en Mora</div>
                <div class="kpi-value text-warning">${{ number_format($totalOverdueAmount, 2) }}</div>
                <div class="kpi-subtitle">{{ $overdueInvoicesCount }} factura(s) con fecha vencida</div>
            </td>
        </tr>
    </table>

    {{-- Customer Detail Section --}}
    @forelse($customerSummaries as $customer)
        @php
            $invoices = $invoicesByCustomer->get($customer->id, collect());
            $custTotalCredit = (float) $customer->total_credit;
            $custTotalPaid = (float) $customer->total_paid;
            $custTotalRemaining = (float) $customer->total_remaining;
        @endphp

        <div class="customer-card">
            <div class="customer-header">
                <table>
                    <tr>
                        <td style="width: 50%;">
                            <span class="customer-name">{{ $customer->customer_name }}</span>
                            <div class="customer-meta">
                                Doc: {{ $customer->document_number }}
                                @if($customer->phone) · Tel: {{ $customer->phone }} @endif
                            </div>
                        </td>
                        <td class="customer-totals" style="width: 50%;">
                            <span><strong>Facturas:</strong> {{ $customer->total_invoices }}</span>
                            <span><strong>Total:</strong> ${{ number_format($custTotalCredit, 2) }}</span>
                            <span><strong>Pagado:</strong> <span style="color: #059669;">${{ number_format($custTotalPaid, 2) }}</span></span>
                            <span><strong>Saldo:</strong> <span style="color: #dc2626; font-weight: bold;">${{ number_format($custTotalRemaining, 2) }}</span></span>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Factura</th>
                        <th style="width: 8%;">Fecha</th>
                        <th style="width: 8%;">Vencimiento</th>
                        <th class="text-center" style="width: 11%;">Días en Mora</th>
                        <th style="width: 15%;">Vendedor</th>
                        <th class="text-right" style="width: 11%;">Total Venta</th>
                        <th class="text-right" style="width: 11%;">Total Crédito</th>
                        <th class="text-right" style="width: 11%;">Pagado</th>
                        <th class="text-right" style="width: 11%;">Pendiente</th>
                        <th class="text-center" style="width: 8%;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                        @php
                            $rem = (float) $inv->credit_amount - (float) $inv->paid_amount;
                            $dueDate = $inv->payment_due_date ?? ($inv->created_at ? $inv->created_at->copy()->addDays(30) : null);
                            $daysOverdue = $inv->days_overdue;
                        @endphp
                        <tr>
                            <td class="font-bold">{{ $inv->invoice_number }}</td>
                            <td>{{ $inv->created_at->format('d/m/Y') }}</td>
                            <td>{{ $dueDate ? $dueDate->format('d/m/Y') : '-' }}</td>
                            <td class="text-center">
                                @if($inv->payment_status === 'paid' || $rem <= 0)
                                    <span class="badge badge-settled">Saldado</span>
                                @elseif($daysOverdue === 0)
                                    <span class="badge badge-ontime">Al día</span>
                                @elseif($daysOverdue <= 30)
                                    <span class="badge badge-mora-low">{{ $daysOverdue }} {{ $daysOverdue == 1 ? 'día' : 'días' }}</span>
                                @elseif($daysOverdue <= 60)
                                    <span class="badge badge-mora-mid">{{ $daysOverdue }} días</span>
                                @else
                                    <span class="badge badge-mora-high">{{ $daysOverdue }} días</span>
                                @endif
                            </td>
                            <td>{{ $inv->seller?->name ?? 'Sin asignar' }}</td>
                            <td class="text-right">${{ number_format($inv->total, 2) }}</td>
                            <td class="text-right font-bold">${{ number_format($inv->credit_amount, 2) }}</td>
                            <td class="text-right" style="color: #059669;">${{ number_format($inv->paid_amount, 2) }}</td>
                            <td class="text-right font-bold" style="color: {{ $rem > 0 ? '#dc2626' : '#64748b' }};">
                                ${{ number_format($rem, 2) }}
                            </td>
                            <td class="text-center">
                                @if($inv->payment_status === 'paid')
                                    <span class="badge badge-paid">Pagado</span>
                                @elseif($inv->payment_status === 'partial')
                                    <span class="badge badge-partial">Parcial</span>
                                @else
                                    <span class="badge badge-pending">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="6" style="text-align: right; text-transform: uppercase;">
                            Subtotal {{ $customer->customer_name }}:
                        </td>
                        <td class="text-right">${{ number_format($custTotalCredit, 2) }}</td>
                        <td class="text-right" style="color: #059669;">${{ number_format($custTotalPaid, 2) }}</td>
                        <td class="text-right" style="color: #dc2626; font-weight: bold;">${{ number_format($custTotalRemaining, 2) }}</td>
                        <td class="text-center" style="color: #64748b;">{{ $customer->total_invoices }} fac.</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <div class="empty-state">
            No se encontraron registros de créditos o cartera para los filtros seleccionados.
        </div>
    @endforelse

    {{-- Grand Totals Card --}}
    @if(count($customerSummaries) > 0)
    <div class="grand-total-box">
        <table class="grand-total-table">
            <tr>
                <td style="width: 35%;">
                    <div class="grand-total-title">Consolidado General de Cartera</div>
                    <div class="grand-total-desc">
                        {{ number_format(count($customerSummaries)) }} clientes · {{ number_format($grandTotalInvoices) }} facturas de crédito analizadas
                    </div>
                </td>
                <td class="grand-total-stat" style="width: 16%;">
                    <div class="lbl">Total Créditos</div>
                    <div class="val">${{ number_format($grandTotalCredit, 2) }}</div>
                </td>
                <td class="grand-total-stat" style="width: 16%;">
                    <div class="lbl">Total Cobrado</div>
                    <div class="val green">${{ number_format($grandTotalPaid, 2) }}</div>
                </td>
                <td class="grand-total-stat" style="width: 16%;">
                    <div class="lbl">Total Pendiente</div>
                    <div class="val red">${{ number_format($grandTotalRemaining, 2) }}</div>
                </td>
                <td class="grand-total-stat" style="width: 17%;">
                    <div class="lbl">Cartera en Mora</div>
                    <div class="val red">${{ number_format($totalOverdueAmount, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Footer with Page Script --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->page_text(400, 568, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 7.5, array(0.58, 0.64, 0.72));
            $pdf->page_text(30, 568, "MikPOS · Reporte Oficial de Cartera y Créditos", $font, 7.5, array(0.58, 0.64, 0.72));
        }
    </script>
</body>
</html>
