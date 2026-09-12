@php
    $first = $payments->first();
    $branch = $first?->branch;
    $isReceivable = $first ? $first->isReceivable() : true;
    $docTitle = $isReceivable ? 'RECIBO DE CAJA' : 'COMPROBANTE DE EGRESO';
    $subTitle = $isReceivable ? 'ABONO DE CARTERA' : 'PAGO A PROVEEDOR';
    $user = $first?->user;
    $customer = $first?->customer;
    $supplier = $first?->supplier;

    // Entity details
    $entityType = $isReceivable ? 'CLIENTE' : 'PROVEEDOR';
    $entityName = $isReceivable 
        ? ($customer ? ($customer->customer_type === 'juridico' ? ($customer->business_name ?: $customer->full_name) : $customer->full_name) : 'Consumidor Final')
        : ($supplier ? $supplier->name : 'Proveedor General');
    $entityDoc = $isReceivable
        ? ($customer ? ($customer->taxDocument?->abbreviation ?? 'Doc') . ': ' . $customer->document_number : null)
        : ($supplier ? ($supplier->taxDocument?->abbreviation ?? 'Doc') . ': ' . $supplier->document_number : null);
    $entityPhone = $isReceivable ? $customer?->phone : $supplier?->phone;

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
    <meta name="viewport" content="width=72mm">
    <title>{{ $docTitle }} #{{ $receiptNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 72mm auto;
            margin: 0mm;
        }

        body {
            font-family: 'Arial Black', 'Arial Bold', 'Helvetica Bold', Arial, sans-serif;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.4;
            width: 72mm;
            max-width: 72mm;
            padding: 2mm;
            background: #fff;
            color: #000;
            -webkit-print-color-adjust: exact;
        }

        .receipt {
            width: 100%;
        }

        /* Header */
        .header {
            text-align: center;
            padding-bottom: 6px;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }

        .branch-logo {
            max-width: 180px;
            max-height: 70px;
            object-fit: contain;
            margin-bottom: 5px;
        }

        .business-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            word-wrap: break-word;
        }

        .business-info {
            font-size: 10px;
        }

        .business-info p {
            margin: 1px 0;
        }

        /* Document Info */
        .doc-info {
            text-align: center;
            padding: 6px 0;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }

        .doc-type {
            display: inline-block;
            padding: 2px 8px;
            background: #000;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .doc-number {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .date-time {
            font-size: 10px;
        }

        /* Section */
        .section {
            padding: 5px 0;
            border-bottom: 1px dashed #000;
            margin-bottom: 5px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            color: #333;
        }

        .field-value {
            font-size: 11px;
            word-wrap: break-word;
        }

        .field-sub {
            font-size: 10px;
            color: #222;
        }

        /* Table */
        .invoices-header {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }

        .inv-row {
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px dotted #888;
        }

        .inv-row:last-child {
            border-bottom: none;
        }

        .inv-num {
            font-size: 11px;
            font-weight: bold;
        }

        .inv-sub {
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #333;
        }

        /* Payments */
        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-top: 2px;
        }

        /* Totals */
        .totals-section {
            border-top: 1px solid #000;
            padding-top: 6px;
            margin-bottom: 8px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            margin-bottom: 6px;
        }

        .signature-line {
            width: 47%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 8.5px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 6px;
            border-top: 1px dashed #000;
            font-size: 9px;
            color: #333;
        }

        /* Actions */
        .print-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
            z-index: 100;
        }

        .btn {
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-print {
            background: linear-gradient(135deg, #ff7261, #a855f7);
            color: white;
        }

        .btn-close {
            background: #6b7280;
            color: white;
        }

        @media print {
            body {
                width: 72mm;
                max-width: 72mm;
                padding: 1mm;
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

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            @if($showLogo && $branch?->logo)
            <div style="text-align:center;">
                <img src="{{ Storage::url($branch->logo) }}" alt="{{ $branch->name }}" class="branch-logo">
            </div>
            @endif
            <div class="business-name">{{ $branch?->name ?? config('app.name') }}</div>
            <div class="business-info">
                @if($branch?->tax_id)<p>NIT: {{ $branch->tax_id }}</p>@endif
                @if($branch?->address)<p>{{ $branch->address }}</p>@endif
                @if($branch?->municipality)<p>{{ $branch->municipality->name }}@if($branch->department), {{ $branch->department->name }}@endif</p>@endif
                @if($branch?->phone)<p>Tel: {{ $branch->phone }}</p>@endif
            </div>
        </div>

        <!-- Document Info -->
        <div class="doc-info">
            <span class="doc-type">{{ $docTitle }}</span>
            <div class="doc-number">{{ $receiptNumber }}</div>
            <div class="date-time">Fecha: {{ $first?->created_at ? $first->created_at->format('d/m/Y H:i') : date('d/m/Y H:i') }}</div>
        </div>

        <!-- Third Party (Customer or Supplier) -->
        <div class="section">
            <div class="section-title">{{ $entityType }}:</div>
            <div class="field-value">{{ $entityName }}</div>
            @if($entityDoc)
            <div class="field-sub">{{ $entityDoc }}</div>
            @endif
            @if($entityPhone)
            <div class="field-sub">Tel: {{ $entityPhone }}</div>
            @endif
        </div>

        <!-- Responsible / Cajero -->
        <div class="section">
            <div class="section-title">REGISTRADO POR:</div>
            <div class="field-value">{{ $user?->name ?? 'Usuario' }}</div>
            @if($branch)
            <div class="field-sub">Sucursal: {{ $branch->name }}</div>
            @endif
        </div>

        <!-- Invoices Affected (One or Multiple) -->
        <div class="section">
            <div class="section-title">FACTURA(S) ABONADA(S):</div>
            <div class="invoices-header">
                <span>Factura</span>
                <span>Abono</span>
            </div>
            @foreach($invoices as $inv)
            <div class="inv-row">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="inv-num">{{ $inv['number'] }}</span>
                    <span style="font-size: 11px; font-weight: bold;">${{ number_format($inv['allocated'], 2) }}</span>
                </div>
                <div class="inv-sub">
                    <span>Total Fact: ${{ number_format($inv['total'], 2) }}</span>
                    <span>Saldo Restante: ${{ number_format($inv['current_remaining'], 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Payment Methods -->
        <div class="section">
            <div class="section-title">FORMA(S) DE PAGO:</div>
            @foreach($methods as $methodName => $methodAmount)
            <div class="payment-row">
                <span>• {{ $methodName }}:</span>
                <span>${{ number_format($methodAmount, 2) }}</span>
            </div>
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span>TOTAL ABONADO:</span>
                <span>${{ number_format($totalAmount, 2) }}</span>
            </div>
        </div>

        <!-- Notes / Observations -->
        @if($showObservations && !empty($allNotes))
        <div class="section">
            <div class="section-title">OBSERVACIONES:</div>
            <div style="font-size: 9.5px; font-style: italic; word-wrap: break-word;">{{ $allNotes }}</div>
        </div>
        @endif

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-line">
                Entrega (Cliente/Proveedor)<br>
                C.C. / NIT
            </div>
            <div class="signature-line">
                Recibe (Cajero/Responsable)<br>
                {{ Str::limit($user?->name ?? '', 20) }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Comprobante de Abono - Soporte de Cartera</p>
            <p style="font-size: 8px; margin-top: 2px;">Sistema POS - {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
