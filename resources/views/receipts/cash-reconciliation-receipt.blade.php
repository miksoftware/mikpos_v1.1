<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=80mm">
    <title>Cierre de Caja - {{ $reconciliation->cashRegister->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 80mm auto; margin: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.5;
            width: 80mm;
            max-width: 80mm;
            padding: 10px;
            background: #fff;
            color: #000;
        }
        .receipt { width: 100%; }
        .header { text-align: center; padding-bottom: 10px; border-bottom: 2px dashed #000; margin-bottom: 10px; }
        .business-name { font-size: 20px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
        .business-info { font-size: 12px; color: #333; }
        .business-info p { margin: 2px 0; }
        .title { text-align: center; font-size: 16px; font-weight: bold; padding: 8px 0; border-bottom: 2px dashed #000; margin-bottom: 10px; text-transform: uppercase; }
        .section { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #999; }
        .section-title { font-size: 13px; font-weight: bold; margin-bottom: 6px; text-transform: uppercase; text-decoration: underline; }
        .row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 12px; }
        .row .label { color: #333; }
        .row .value { font-weight: bold; text-align: right; }
        .row.total { font-size: 14px; font-weight: bold; border-top: 1px solid #000; padding-top: 4px; margin-top: 4px; }
        .row.highlight { font-size: 14px; }
        .row.positive .value { }
        .row.negative .value { }
        .difference-box { text-align: center; padding: 8px; margin: 8px 0; border: 2px solid #000; font-size: 16px; font-weight: bold; }
        .difference-box.sobrante { background: #f0fff0; }
        .difference-box.faltante { background: #fff0f0; }
        .difference-box.exacto { background: #f0f0ff; }
        .movement-item { padding: 3px 0; font-size: 11px; border-bottom: 1px dotted #ccc; }
        .movement-item:last-child { border-bottom: none; }
        .footer { text-align: center; padding-top: 10px; border-top: 2px dashed #000; margin-top: 10px; font-size: 11px; color: #666; }
        .signatures { margin-top: 30px; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 30px auto 5px; }
        .signature-label { text-align: center; font-size: 11px; color: #333; }
        @media print {
            body { padding: 5px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <div class="no-print" style="text-align: center; padding: 10px; background: #f5f5f5; margin-bottom: 10px; border-radius: 8px;">
        <button onclick="window.print()" style="padding: 8px 24px; background: #000; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: bold;">
            🖨️ Imprimir
        </button>
    </div>

    @php
        $salesByMethod = $reconciliation->getSalesByPaymentMethod();
        $totalSales = $reconciliation->total_sales;
        $salesCount = $reconciliation->sales_count;
        $cashSales = $reconciliation->total_cash_sales;
        $totalIncome = $reconciliation->total_income;
        $totalExpenses = $reconciliation->total_expenses;
        $expectedAmount = $reconciliation->expected_amount ?? $reconciliation->calculateExpectedAmount();
        $difference = $reconciliation->difference ?? ($reconciliation->closing_amount - $expectedAmount);
    @endphp

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="business-name">{{ $reconciliation->branch->name ?? 'MikPOS' }}</div>
            <div class="business-info">
                @if($reconciliation->branch->tax_id)
                <p><strong>NIT:</strong> {{ $reconciliation->branch->tax_id }}</p>
                @endif
                @if($reconciliation->branch->address)
                <p>{{ $reconciliation->branch->address }}</p>
                @endif
                @if($reconciliation->branch->municipality)
                <p>{{ $reconciliation->branch->municipality->name }}@if($reconciliation->branch->department), {{ $reconciliation->branch->department->name }}@endif</p>
                @endif
                @if($reconciliation->branch->phone)
                <p>Tel: {{ $reconciliation->branch->phone }}</p>
                @endif
            </div>
        </div>

        <div class="title">CIERRE DE CAJA</div>

        <!-- Cash Register Info -->
        <div class="section">
            <div class="row">
                <span class="label">Caja:</span>
                <span class="value">{{ $reconciliation->cashRegister->name }}</span>
            </div>
            <div class="row">
                <span class="label">Nº Caja:</span>
                <span class="value">{{ $reconciliation->cashRegister->number }}</span>
            </div>
            <div class="row">
                <span class="label">Apertura:</span>
                <span class="value">{{ $reconciliation->opened_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row">
                <span class="label">Cierre:</span>
                <span class="value">{{ $reconciliation->closed_at ? $reconciliation->closed_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row">
                <span class="label">Abierta por:</span>
                <span class="value">{{ $reconciliation->openedByUser->name }}</span>
            </div>
            <div class="row">
                <span class="label">Cerrada por:</span>
                <span class="value">{{ $reconciliation->closedByUser->name ?? auth()->user()->name }}</span>
            </div>
        </div>

        <!-- Sales by Payment Method -->
        <div class="section">
            <div class="section-title">Ventas del Turno ({{ $salesCount }})</div>
            @if($salesCount > 0)
                @foreach($salesByMethod as $method)
                <div class="row">
                    <span class="label">{{ $method['method_name'] }} ({{ $method['count'] }})</span>
                    <span class="value">${{ number_format($method['total'], 2) }}</span>
                </div>
                @endforeach
                <div class="row total">
                    <span class="label">TOTAL VENTAS:</span>
                    <span class="value">${{ number_format($totalSales, 2) }}</span>
                </div>
            @else
                <p style="text-align: center; font-size: 12px; color: #999; padding: 4px 0;">Sin ventas</p>
            @endif
        </div>

        <!-- Cash Movements -->
        <div class="section">
            <div class="section-title">Movimientos de Caja</div>
            @if($reconciliation->movements->count() > 0)
                @foreach($reconciliation->movements as $movement)
                <div class="movement-item">
                    <div class="row">
                        <span class="label">{{ $movement->type === 'income' ? '(+)' : '(-)' }} {{ $movement->concept }}</span>
                        <span class="value">${{ number_format($movement->amount, 2) }}</span>
                    </div>
                    <div style="font-size: 10px; color: #666; padding-left: 4px;">
                        {{ $movement->created_at->format('H:i') }} - {{ $movement->user->name }}
                    </div>
                </div>
                @endforeach
                <div class="row" style="margin-top: 4px;">
                    <span class="label">Total Ingresos:</span>
                    <span class="value" style="color: #060;">${{ number_format($totalIncome, 2) }}</span>
                </div>
                <div class="row">
                    <span class="label">Total Egresos:</span>
                    <span class="value" style="color: #600;">${{ number_format($totalExpenses, 2) }}</span>
                </div>
            @else
                <p style="text-align: center; font-size: 12px; color: #999; padding: 4px 0;">Sin movimientos</p>
            @endif
        </div>

        <!-- Credit Payments -->
        @php
            $creditPayments = \App\Models\CreditPayment::with(['supplier', 'customer', 'paymentMethod'])
                ->where('cash_reconciliation_id', $reconciliation->id)
                ->where('affects_cash', true)
                ->get();
            $creditPayable = $creditPayments->where('credit_type', 'payable');
            $creditReceivable = $creditPayments->where('credit_type', 'receivable');
        @endphp
        @if($creditPayments->count() > 0)
        <div class="section">
            <div class="section-title">Pagos de Créditos</div>
            @foreach($creditPayable as $cp)
            <div class="movement-item">
                <div class="row">
                    <span class="label">(-) Pago prov: {{ $cp->supplier->name ?? '-' }}</span>
                    <span class="value">${{ number_format($cp->amount, 2) }}</span>
                </div>
                <div style="font-size: 10px; color: #666; padding-left: 4px;">
                    {{ $cp->paymentMethod->name ?? '' }} · {{ $cp->created_at->format('H:i') }}
                </div>
            </div>
            @endforeach
            @foreach($creditReceivable as $cp)
            <div class="movement-item">
                <div class="row">
                    <span class="label">(+) Cobro cliente: {{ $cp->customer->first_name ?? '-' }}</span>
                    <span class="value">${{ number_format($cp->amount, 2) }}</span>
                </div>
                <div style="font-size: 10px; color: #666; padding-left: 4px;">
                    {{ $cp->paymentMethod->name ?? '' }} · {{ $cp->created_at->format('H:i') }}
                </div>
            </div>
            @endforeach
            @if($creditPayable->count() > 0)
            <div class="row" style="margin-top: 4px;">
                <span class="label">Total pagos proveedores:</span>
                <span class="value" style="color: #600;">${{ number_format($creditPayable->sum('amount'), 2) }}</span>
            </div>
            @endif
            @if($creditReceivable->count() > 0)
            <div class="row">
                <span class="label">Total cobros clientes:</span>
                <span class="value" style="color: #060;">${{ number_format($creditReceivable->sum('amount'), 2) }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Cash Summary -->
        <div class="section">
            <div class="section-title">Resumen de Efectivo</div>
            <div class="row">
                <span class="label">Monto inicial:</span>
                <span class="value">${{ number_format($reconciliation->opening_amount, 2) }}</span>
            </div>
            @if($cashSales > 0)
            <div class="row positive">
                <span class="label">(+) Ventas efectivo:</span>
                <span class="value">${{ number_format($cashSales, 2) }}</span>
            </div>
            @endif
            @if($totalIncome > 0)
            <div class="row positive">
                <span class="label">(+) Otros ingresos:</span>
                <span class="value">${{ number_format($totalIncome, 2) }}</span>
            </div>
            @endif
            @if($totalExpenses > 0)
            <div class="row negative">
                <span class="label">(-) Egresos:</span>
                <span class="value">${{ number_format($totalExpenses, 2) }}</span>
            </div>
            @endif
            <div class="row total">
                <span class="label">EFECTIVO ESPERADO:</span>
                <span class="value">${{ number_format($expectedAmount, 2) }}</span>
            </div>
            <div class="row total">
                <span class="label">EFECTIVO CONTADO:</span>
                <span class="value">${{ number_format($reconciliation->closing_amount, 2) }}</span>
            </div>
        </div>

        <!-- Difference -->
        @php
            $diffClass = $difference == 0 ? 'exacto' : ($difference > 0 ? 'sobrante' : 'faltante');
            $diffLabel = $difference == 0 ? 'CUADRE EXACTO' : ($difference > 0 ? 'SOBRANTE' : 'FALTANTE');
        @endphp
        <div class="difference-box {{ $diffClass }}">
            {{ $diffLabel }}<br>
            ${{ number_format(abs($difference), 2) }}
        </div>

        <!-- Notes -->
        @if($reconciliation->opening_notes || $reconciliation->closing_notes)
        <div class="section">
            <div class="section-title">Observaciones</div>
            @if($reconciliation->opening_notes)
            <div style="font-size: 11px; margin-bottom: 4px;">
                <strong>Apertura:</strong> {{ $reconciliation->opening_notes }}
            </div>
            @endif
            @if($reconciliation->closing_notes)
            <div style="font-size: 11px;">
                <strong>Cierre:</strong> {{ $reconciliation->closing_notes }}
            </div>
            @endif
        </div>
        @endif

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-line"></div>
            <div class="signature-label">Cajero / Responsable</div>
            <div class="signature-line"></div>
            <div class="signature-label">Supervisor / Administrador</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ $reconciliation->branch->name ?? 'MikPOS' }}</p>
            <p>Impreso: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
