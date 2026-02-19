<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=72mm">
    <title>Recibo #{{ $sale->invoice_number }}</title>
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
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
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
        
        .business-name {
            font-size: 16px;
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
        
        /* Invoice Info */
        .invoice-info {
            text-align: center;
            padding: 6px 0;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }
        
        .invoice-number {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .invoice-type {
            display: inline-block;
            padding: 1px 6px;
            background: #000;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .invoice-type.electronic {
            background: #000;
        }
        
        .date-time {
            font-size: 10px;
        }
        
        /* Customer Info */
        .customer-section {
            padding: 6px 0;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }
        
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        
        .customer-type-badge {
            display: inline-block;
            padding: 1px 4px;
            background: #ddd;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .customer-name {
            font-weight: bold;
            font-size: 11px;
            word-wrap: break-word;
        }
        
        .customer-doc {
            font-size: 10px;
        }
        
        /* Items */
        .items-section {
            margin-bottom: 6px;
        }
        
        .items-header {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding-bottom: 3px;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }
        
        .item {
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px dotted #999;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: bold;
            font-size: 10px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        
        .item-qty-price {
            color: #333;
        }
        
        .item-total {
            font-weight: bold;
            white-space: nowrap;
        }
        
        /* Totals */
        .totals-section {
            border-top: 1px solid #000;
            padding-top: 6px;
            margin-bottom: 6px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 2px;
        }
        
        .total-row.grand-total {
            font-size: 15px;
            font-weight: bold;
            padding-top: 4px;
            border-top: 1px dashed #000;
            margin-top: 4px;
        }
        
        /* Payments */
        .payments-section {
            padding: 6px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            margin-bottom: 6px;
        }
        
        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 2px;
        }
        
        .payment-method {
            font-weight: bold;
        }
        
        .change-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px dotted #999;
        }
        
        /* DIAN Info */
        .dian-section {
            text-align: center;
            padding: 6px 4px;
            border: 1px solid #000;
            margin-bottom: 6px;
        }
        
        .dian-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .cufe-label {
            font-size: 9px;
            font-weight: bold;
        }
        
        .cufe {
            font-size: 7px;
            word-break: break-all;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        
        .qr-container {
            text-align: center;
            margin: 4px 0;
        }
        
        .qr-container img {
            max-width: 90px;
            height: auto;
        }
        
        .qr-label {
            font-size: 8px;
            margin-top: 2px;
        }
        
        /* Seller Info */
        .seller-section {
            font-size: 10px;
            text-align: center;
            margin-bottom: 6px;
            padding: 4px 0;
        }
        
        .seller-section p {
            margin: 1px 0;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 6px;
            border-top: 1px dashed #000;
        }
        
        .thank-you {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .footer-message {
            font-size: 10px;
            margin-bottom: 6px;
        }
        
        .powered-by {
            font-size: 9px;
            color: #555;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px dotted #ccc;
        }
        
        /* Print styles */
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
        
        /* Print button (only visible on screen) */
        .print-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
            z-index: 100;
        }
        
        .btn {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
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
    </style>
</head>
<body>
    <!-- Print Actions (hidden when printing) -->
    <div class="print-actions no-print">
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button class="btn btn-close" onclick="window.close()">✕ Cerrar</button>
    </div>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="business-name">{{ $sale->branch->name }}</div>
            <div class="business-info">
                @if($sale->branch->tax_id)
                <p><strong>NIT:</strong> {{ $sale->branch->tax_id }}</p>
                @endif
                @if($sale->branch->address)
                <p>{{ $sale->branch->address }}</p>
                @endif
                @if($sale->branch->municipality)
                <p>{{ $sale->branch->municipality->name }}@if($sale->branch->department), {{ $sale->branch->department->name }}@endif</p>
                @endif
                @if($sale->branch->phone)
                <p>Tel: {{ $sale->branch->phone }}</p>
                @endif
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            @if($sale->is_electronic && $sale->cufe)
            <span class="invoice-type electronic">FACTURA ELECTRÓNICA</span>
            <div class="invoice-number">{{ $sale->dian_number ?? $sale->invoice_number }}</div>
            @else
            <span class="invoice-type pos">DOCUMENTO POS</span>
            <div class="invoice-number">{{ $sale->invoice_number }}</div>
            @endif
            <div class="date-time">
                {{ $sale->created_at->format('d/m/Y') }} - {{ $sale->created_at->format('H:i:s') }}
            </div>
        </div>

        <!-- Customer -->
        <div class="customer-section">
            <div class="section-title">Cliente</div>
            @if($sale->customer)
            <span class="customer-type-badge">
                {{ $sale->customer->customer_type === 'juridico' ? 'Persona Jurídica' : 'Persona Natural' }}
            </span>
            <div class="customer-name">{{ $sale->customer->full_name }}</div>
            @if($sale->customer->document_number)
            <div class="customer-doc">{{ $sale->customer->taxDocument->abbreviation ?? 'Doc' }}: {{ $sale->customer->document_number }}</div>
            @endif
            @else
            <span class="customer-type-badge">Persona Natural</span>
            <div class="customer-name">Consumidor Final</div>
            @endif
        </div>

        <!-- Items -->
        <div class="items-section">
            <div class="section-title">Detalle de Productos</div>
            <div class="items-header">
                <span>Producto</span>
                <span>Total</span>
            </div>
            @foreach($sale->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->product_name }}</div>
                <div class="item-details">
                    <span class="item-qty-price">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }} x ${{ number_format($item->unit_price, 0) }}</span>
                    <span class="item-total">${{ number_format($item->subtotal, 0) }}</span>
                </div>
                @if($item->discount_amount > 0)
                <div class="item-details" style="font-size: 9px;">
                    <span>
                        Desc: {{ $item->discount_type === 'percentage' ? $item->discount_type_value . '%' : '$' . number_format($item->discount_type_value, 0) }}
                        @if($item->discount_reason) ({{ $item->discount_reason }}) @endif
                    </span>
                    <span>-${{ number_format($item->discount_amount, 0) }}</span>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row subtotal">
                <span>Subtotal</span>
                <span>${{ number_format($sale->subtotal, 0) }}</span>
            </div>
            @if($sale->tax_total > 0)
            <div class="total-row tax">
                <span>IVA</span>
                <span>${{ number_format($sale->tax_total, 0) }}</span>
            </div>
            @endif
            @if($sale->discount > 0)
            <div class="total-row discount">
                <span>Descuento</span>
                <span>-${{ number_format($sale->discount, 0) }}</span>
            </div>
            @endif
            <div class="total-row grand-total">
                <span>TOTAL</span>
                <span>${{ number_format($sale->total, 0) }}</span>
            </div>
        </div>

        <!-- Payments -->
        <div class="payments-section">
            <div class="section-title">Forma de Pago</div>
            @php
                $totalPaid = 0;
            @endphp
            @foreach($sale->payments as $payment)
            @php
                $totalPaid += $payment->amount;
            @endphp
            <div class="payment-row">
                <span class="payment-method">{{ $payment->paymentMethod->name }}</span>
                <span>${{ number_format($payment->amount, 0) }}</span>
            </div>
            @endforeach
            @if($totalPaid > $sale->total)
            <div class="change-row">
                <span>Cambio</span>
                <span>${{ number_format($totalPaid - $sale->total, 0) }}</span>
            </div>
            @endif
        </div>

        <!-- DIAN Info (only if electronic AND validated with CUFE) -->
        @if($sale->is_electronic && $sale->cufe)
        <div class="dian-section">
            <div class="dian-title">✓ VALIDADA POR LA DIAN</div>
            <div class="cufe-label">CUFE:</div>
            <div class="cufe">{{ $sale->cufe }}</div>
            @if($sale->qr_code)
            <div class="qr-container">
                <img src="{{ $sale->qr_code }}" alt="QR DIAN" onerror="this.style.display='none'">
                <div class="qr-label">Escanea para verificar en DIAN</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Seller -->
        <div class="seller-section">
            <p><strong>Atendido por:</strong> {{ $sale->user->name }}</p>
            @if($sale->cashReconciliation && $sale->cashReconciliation->cashRegister)
            <p><strong>Caja:</strong> {{ $sale->cashReconciliation->cashRegister->name }}</p>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">¡Gracias por su compra!</div>
            <div class="footer-message">
                @if($sale->branch->receipt_header)
                {{ $sale->branch->receipt_header }}
                @else
                Conserve este documento como comprobante de su compra
                @endif
            </div>
            <div class="powered-by">
                {{ $sale->branch->name }}<br>
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'auto') {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        };
    </script>
</body>
</html>