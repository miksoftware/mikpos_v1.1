@php
    $options = \App\Models\PrintFormatSetting::getLetterOptions('credit_payment');

    $first = $payments->first();
    $branch = $first?->branch;
    $isReceivable = $first ? $first->isReceivable() : true;
    $docTitle = $isReceivable ? 'RECIBO DE CAJA' : 'COMPROBANTE DE EGRESO';
    $subTitle = $isReceivable ? 'ABONO DE CARTERA / CLIENTE' : 'PAGO A PROVEEDOR';
    $user = $first?->user;
    $customer = $first?->customer;
    $supplier = $first?->supplier;

    // Entity details
    $entityType = $isReceivable ? 'Cliente' : 'Proveedor';
    $entityName = $isReceivable 
        ? ($customer ? ($customer->customer_type === 'juridico' ? ($customer->business_name ?: $customer->full_name) : $customer->full_name) : 'Consumidor Final')
        : ($supplier ? $supplier->name : 'Proveedor General');
    $entityDocType = $isReceivable
        ? ($customer?->taxDocument?->abbreviation ?? 'Doc')
        : ($supplier?->taxDocument?->abbreviation ?? 'Doc');
    $entityDocNum = $isReceivable ? $customer?->document_number : $supplier?->document_number;
    $entityPhone = $isReceivable ? $customer?->phone : $supplier?->phone;
    $entityAddress = $isReceivable ? $customer?->address : $supplier?->address;
    $entityCity = $isReceivable ? $customer?->municipality?->name : $supplier?->municipality?->name;

    // Invoices breakdown
    $invoices = [];
    foreach ($payments as $p) {
        $invKey = $p->sale_id ? 'sale_' . $p->sale_id : ($p->purchase_id ? 'purchase_' . $p->purchase_id : 'p_' . $p->id);
        if (!isset($invoices[$invKey])) {
            $docNumber = $p->sale ? $p->sale->invoice_number : ($p->purchase ? $p->purchase->purchase_number : ($p->invoice_number ?: 'N/A'));
            $invDate = $p->sale ? $p->sale->created_at->format('d/m/Y') : ($p->purchase ? $p->purchase->created_at->format('d/m/Y') : '-');
            $invTotal = $p->sale ? (float)$p->sale->total : ($p->purchase ? (float)$p->purchase->total : (float)$p->amount);
            $invPaid = $p->sale ? (float)$p->sale->paid_amount : ($p->purchase ? (float)$p->purchase->paid_amount : (float)$p->amount);
            $invCredit = $p->sale ? (float)$p->sale->credit_amount : ($p->purchase ? (float)$p->purchase->credit_amount : (float)$p->amount);
            $invoices[$invKey] = [
                'number' => $docNumber,
                'date' => $invDate,
                'total' => $invTotal,
                'current_remaining' => max(0, $invCredit - $invPaid),
                'allocated' => 0,
            ];
        }
        $invoices[$invKey]['allocated'] += (float)$p->amount;
    }

    // Payment methods breakdown
    $methods = [];
    foreach ($payments as $p) {
        $mName = $p->paymentMethod?->name ?? 'Otro';
        $methods[$mName] = ($methods[$mName] ?? 0) + (float)$p->amount;
    }

    $totalAmount = $payments->sum('amount');
    $allNotes = $payments->pluck('notes')->filter()->unique()->join(' | ');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docTitle }} {{ $receiptNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: letter;
            margin: 12mm 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            background: #fff;
            -webkit-print-color-adjust: exact;
        }

        .document-container {
            max-width: 760px;
            margin: 0 auto;
            padding: 15px 20px;
        }

        /* Header */
        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 12px;
            border-bottom: 2px solid {{ $isReceivable ? '#2563eb' : '#dc2626' }};
            margin-bottom: 15px;
        }

        .logo-box img {
            max-width: 170px;
            max-height: 60px;
            object-fit: contain;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-main-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
            letter-spacing: 0.5px;
        }

        .doc-badge {
            display: inline-block;
            background: {{ $isReceivable ? '#2563eb' : '#dc2626' }};
            color: #fff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .doc-number {
            font-size: 15px;
            font-weight: bold;
            color: {{ $isReceivable ? '#2563eb' : '#dc2626' }};
            margin-top: 3px;
        }

        .doc-date {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Info Grid */
        .info-grid {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }

        .info-card {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .info-card-title {
            font-size: 9.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .info-card-content {
            font-size: 10.5px;
            color: #334155;
            line-height: 1.5;
        }

        .info-card-content strong {
            color: #0f172a;
        }

        /* Invoices Table */
        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .invoices-table thead th {
            background: {{ $isReceivable ? '#eff6ff' : '#fef2f2' }};
            border: 1px solid {{ $isReceivable ? '#bfdbfe' : '#fecaca' }};
            padding: 7px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: {{ $isReceivable ? '#1e40af' : '#991b1b' }};
        }

        .invoices-table tbody td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            font-size: 10.5px;
            vertical-align: middle;
        }

        .invoices-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        /* Totals & Payments Section */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 15px;
        }

        .payment-box {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 10.5px;
            line-height: 1.6;
        }

        .totals-box {
            width: 280px;
            background: {{ $isReceivable ? '#eff6ff' : '#fef2f2' }};
            border: 1px solid {{ $isReceivable ? '#bfdbfe' : '#fecaca' }};
            border-radius: 6px;
            padding: 10px 14px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #475569;
            padding: 2px 0;
        }

        .total-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            color: {{ $isReceivable ? '#2563eb' : '#dc2626' }};
            border-top: 2px solid {{ $isReceivable ? '#2563eb' : '#dc2626' }};
            margin-top: 6px;
            padding-top: 6px;
        }

        /* Words */
        .amount-words {
            font-size: 9.5px;
            color: #475569;
            font-style: italic;
            margin-bottom: 14px;
            padding: 6px 12px;
            background: #f8fafc;
            border-left: 3px solid {{ $isReceivable ? '#2563eb' : '#dc2626' }};
            border-radius: 2px;
        }

        /* Observations */
        .observations-box {
            font-size: 10px;
            color: #334155;
            margin-bottom: 15px;
            padding: 8px 12px;
            background: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 4px;
            line-height: 1.4;
        }

        /* Signatures */
        .signatures-row {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .signature-item {
            width: 220px;
            text-align: center;
            border-top: 1px solid #334155;
            padding-top: 5px;
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
        }

        .signature-sub {
            font-size: 9px;
            font-weight: normal;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer */
        .document-footer {
            text-align: center;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 9.5px;
            color: #94a3b8;
        }

        /* Action Buttons */
        .print-actions {
            position: fixed;
            top: 12px;
            right: 12px;
            display: flex;
            gap: 8px;
            z-index: 100;
        }

        .btn {
            padding: 8px 18px;
            font-size: 12px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-print {
            background: linear-gradient(135deg, #ff7261, #a855f7);
            color: white;
        }

        .btn-close {
            background: #64748b;
            color: white;
        }

        @media print {
            body {
                padding: 0;
            }
            .document-container {
                max-width: 100%;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button class="btn btn-close" onclick="window.close()">✕ Cerrar</button>
    </div>

    <div class="document-container">
        <!-- Header -->
        <div class="document-header">
            <div>
                @if($options['show_logo'] && $branch?->logo)
                <div class="logo-box" style="margin-bottom: 6px;">
                    <img src="{{ Storage::url($branch->logo) }}" alt="{{ $branch->name }}">
                </div>
                @endif
                <div class="doc-main-title">{{ $docTitle }}</div>
                <span class="doc-badge">{{ $subTitle }}</span>
            </div>
            <div class="doc-title-box">
                <div class="doc-number">{{ $receiptNumber }}</div>
                <div class="doc-date">Fecha: {{ $first?->created_at ? $first->created_at->format('d/m/Y H:i') : date('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- 3-Column Info Grid -->
        <div class="info-grid">
            @if($options['show_business'])
            <div class="info-card">
                <div class="info-card-title">Empresa / Emisor</div>
                <div class="info-card-content">
                    <strong>{{ $branch?->name ?? config('app.name') }}</strong><br>
                    @if($branch?->tax_id)NIT: {{ $branch->tax_id }}<br>@endif
                    @if($branch?->address){{ $branch->address }}<br>@endif
                    @if($branch?->municipality)
                        {{ $branch->municipality->name }}@if($branch->department), {{ $branch->department->name }}@endif<br>
                    @endif
                    @if($branch?->phone)Tel: {{ $branch->phone }}@endif
                </div>
            </div>
            @endif

            @if($options['show_customer'])
            <div class="info-card">
                <div class="info-card-title">{{ $entityType }}</div>
                <div class="info-card-content">
                    <strong>{{ $entityName }}</strong><br>
                    @if($entityDocNum)
                        {{ $entityDocType }}: {{ $entityDocNum }}<br>
                    @endif
                    @if($entityPhone)Tel: {{ $entityPhone }}<br>@endif
                    @if($entityAddress){{ $entityAddress }}@if($entityCity), {{ $entityCity }}@endif<br>@endif
                </div>
            </div>
            @endif

            @if($options['show_sale_info'])
            <div class="info-card">
                <div class="info-card-title">Información del Comprobante</div>
                <div class="info-card-content">
                    <strong>N° Recibo:</strong> {{ $receiptNumber }}<br>
                    <strong>Fecha Emisión:</strong> {{ $first?->created_at ? $first->created_at->format('d/m/Y H:i') : date('d/m/Y H:i') }}<br>
                    <strong>Atendido por:</strong> {{ $user?->name ?? 'Usuario' }}<br>
                    <strong>Sucursal:</strong> {{ $branch?->name ?? 'Principal' }}<br>
                    <strong>Estado:</strong> Aplicado
                </div>
            </div>
            @endif
        </div>

        <!-- Invoices Table -->
        <table class="invoices-table">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">#</th>
                    <th style="text-align: left;">FACTURA / DOCUMENTO</th>
                    <th style="width: 90px; text-align: center;">FECHA FACT.</th>
                    <th style="width: 120px; text-align: right;">TOTAL FACTURA</th>
                    <th style="width: 120px; text-align: right;">ABONO APLICADO</th>
                    <th style="width: 130px; text-align: right;">SALDO RESTANTE</th>
                </tr>
            </thead>
            <tbody>
                @php $idx = 1; @endphp
                @foreach($invoices as $inv)
                <tr>
                    <td style="text-align: center; color: #64748b; font-weight: bold;">{{ $idx++ }}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $inv['number'] }}</strong>
                    </td>
                    <td style="text-align: center; color: #64748b;">{{ $inv['date'] }}</td>
                    <td style="text-align: right; color: #475569;">${{ number_format($inv['total'], 2) }}</td>
                    <td style="text-align: right; font-weight: bold; color: {{ $isReceivable ? '#1d4ed8' : '#b91c1c' }}; font-size: 11.5px;">
                        ${{ number_format($inv['allocated'], 2) }}
                    </td>
                    <td style="text-align: right; font-weight: bold; color: #475569;">
                        ${{ number_format($inv['current_remaining'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Payments Row -->
        <div class="bottom-section">
            @if($options['show_payment_info'])
            <div class="payment-box">
                <div style="font-weight: bold; text-transform: uppercase; font-size: 9.5px; color: #475569; margin-bottom: 4px;">
                    Forma(s) de Pago
                </div>
                @foreach($methods as $methodName => $methodAmount)
                <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                    <span>• {{ $methodName }}:</span>
                    <span style="font-weight: 600;">${{ number_format($methodAmount, 2) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div></div>
            @endif

            <div class="totals-box">
                <div class="total-row">
                    <span>Total Factura(s):</span>
                    <span>${{ number_format(collect($invoices)->sum('total'), 2) }}</span>
                </div>
                <div class="total-row grand-total">
                    <span>TOTAL ABONADO:</span>
                    <span>${{ number_format($totalAmount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Amount in Words -->
        @if($options['show_amount_words'])
        @php
            $intPart = intval($totalAmount);
            $decPart = round(($totalAmount - $intPart) * 100);
            $words = '';
            if (class_exists('\NumberFormatter')) {
                try {
                    $formatter = new \NumberFormatter('es_CO', \NumberFormatter::SPELLOUT);
                    $words = mb_strtoupper($formatter->format($intPart));
                } catch (\Throwable $e) {
                    $words = '';
                }
            }
        @endphp
        @if(!empty($words))
        <div class="amount-words">
            <strong>Monto en letras:</strong> {{ $words }} PESOS CON {{ str_pad((string)$decPart, 2, '0', STR_PAD_LEFT) }}/100 M/CTE
        </div>
        @endif
        @endif

        <!-- Observations -->
        @if($options['show_observations'] && !empty($allNotes))
        <div class="observations-box">
            <strong>Observaciones:</strong> {{ $allNotes }}
        </div>
        @endif

        <!-- Signatures -->
        <div class="signatures-row">
            <div class="signature-item">
                Elaborado por (Responsable)
                <div class="signature-sub">{{ $user?->name ?? 'Firma Autorizada' }}</div>
            </div>
            <div class="signature-item">
                Recibí Conforme ({{ $entityType }})
                <div class="signature-sub">
                    {{ $entityName }}<br>
                    {{ $entityDocType }}: {{ $entityDocNum ?? '____________________' }}
                </div>
            </div>
        </div>

        <!-- Footer -->
        @if($options['show_footer'])
        <div class="document-footer">
            <p>Este documento es un comprobante oficial de abono y soporte contable de cartera.</p>
            <p style="font-size: 8.5px; margin-top: 3px;">Sistema POS - {{ config('app.name') }}</p>
        </div>
        @endif
    </div>
</body>
</html>
