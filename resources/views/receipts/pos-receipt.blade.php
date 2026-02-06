<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=80mm">
    <title>Recibo #{{ $sale->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            line-height: 1.5;
            width: 80mm;
            max-width: 80mm;
            padding: 10px;
            background: #fff;
            color: #000;
        }
        
        .receipt {
            width: 100%;
        }
        
        /* Header */
        .header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px dashed #000;
            margin-bottom: 12px;
        }
        
        .business-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        
        .business-info {
            font-size: 13px;
            color: #333;
        }
        
        .business-info p {
            margin: 2px 0;
        }
        
        /* Invoice Info */
        .invoice-info {
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px dashed #000;
            margin-bottom: 12px;
        }
        
        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        
        .invoice-type {
            display: inline-block;
            padding: 4px 12px;
            background: #000;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        
        .invoice-type.electronic {
            background: #16a34a;
        }
        
        .invoice-type.pos {
            background: #374151;
        }
        
        .date-time {
            font-size: 13px;
            margin-top: 4px;
        }
        
        /* Customer Info */
        .customer-section {
            padding: 10px 0;
            border-bottom: 2px dashed #000;
            margin-bottom: 12px;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
            color: #555;
        }
        
        .customer-type-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #e5e7eb;
            font-size: 11px;
            font-weight: bold;
            border-radius: 3px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .customer-name {
            font-weight: bold;
            font-size: 14px;
        }
        
        .customer-doc {
            font-size: 13px;
            margin-top: 2px;
        }
        
        /* Items */
        .items-section {
            margin-bottom: 12px;
        }
        
        .items-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            padding-bottom: 6px;
            border-bottom: 1px solid #999;
            margin-bottom: 8px;
        }
        
        .item {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px dotted #ccc;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        
        .item-qty-price {
            color: #555;
        }
        
        .item-total {
            font-weight: bold;
        }
        
        /* Totals */
        .totals-section {
            border-top: 2px solid #000;
            padding-top: 12px;
            margin-bottom: 12px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 6px;
        }
        
        .total-row.subtotal {
            color: #555;
        }
        
        .total-row.tax {
            color: #555;
        }
        
        .total-row.grand-total {
            font-size: 20px;
            font-weight: bold;
            padding-top: 8px;
            border-top: 2px dashed #000;
            margin-top: 8px;
        }
        
        /* Payments */
        .payments-section {
            padding: 10px 0;
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            margin-bottom: 12px;
        }
        
        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .payment-method {
            font-weight: bold;
        }
        
        .change-row {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            font-weight: bold;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dotted #999;
        }
        
        /* DIAN Info */
        .dian-section {
            text-align: center;
            padding: 10px;
            border: 2px solid #16a34a;
            border-radius: 6px;
            margin-bottom: 12px;
            background: #f0fdf4;
        }
        
        .dian-title {
            font-size: 13px;
            font-weight: bold;
            color: #16a34a;
            margin-bottom: 6px;
        }
        
        .cufe-label {
            font-size: 11px;
            font-weight: bold;
            color: #555;
            margin-bottom: 2px;
        }
        
        .cufe {
            font-size: 9px;
            word-break: break-all;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .qr-container {
            text-align: center;
            margin: 10px 0;
        }
        
        .qr-container img {
            max-width: 120px;
            height: auto;
            border: 1px solid #ddd;
            padding: 4px;
            background: #fff;
        }
        
        .qr-label {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        
        /* Seller Info */
        .seller-section {
            font-size: 12px;
            text-align: center;
            margin-bottom: 12px;
            color: #555;
            padding: 8px 0;
        }
        
        .seller-section p {
            margin: 2px 0;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 12px;
            border-top: 2px dashed #000;
        }
        
        .thank-you {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        
        .footer-message {
            font-size: 12px;
            color: #555;
            margin-bottom: 10px;
        }
        
        .powered-by {
            font-size: 10px;
            color: #999;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dotted #ccc;
        }
        
        /* Print styles */
        @media print {
            body {
                width: 80mm;
                padding: 5px;
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
        }
        
        .btn {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #ff7261, #a855f7);
            color: white;
        }
        
        .btn-print:hover {
            transform: scale(1.05);
        }
        
        .btn-close {
            background: #6b7280;
            color: white;
        }
        
        .btn-close:hover {
            background: #4b5563;
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
                <div class="item-details" style="color: #d97706; font-size: 12px;">
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
                <img src="{{ $sale->qr_code }}" alt="Código QR DIAN" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display:none; font-size:11px; color:#666;">QR no disponible</div>
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
        // Auto-print when opened
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
