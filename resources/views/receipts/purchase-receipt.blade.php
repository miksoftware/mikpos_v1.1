<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=210mm">
    <title>Compra #{{ $purchase->purchase_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1e293b;
            background: #fff;
        }
        
        .receipt {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid #a855f7;
            margin-bottom: 20px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 11px;
            color: #64748b;
        }
        
        .company-details p {
            margin: 2px 0;
        }
        
        .document-info {
            text-align: right;
        }
        
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #a855f7;
            margin-bottom: 5px;
        }
        
        .document-number {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
        }
        
        .document-date {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        /* Info Sections */
        .info-row {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .info-box {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        
        .info-box-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .info-box-content {
            font-size: 12px;
        }
        
        .info-box-content p {
            margin: 3px 0;
        }
        
        .info-box-content strong {
            color: #1e293b;
        }
        
        /* Items Table */
        .items-section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead th {
            background: #a855f7;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        thead th:first-child {
            border-radius: 6px 0 0 0;
        }
        
        thead th:last-child {
            border-radius: 0 6px 0 0;
            text-align: right;
        }
        
        thead th.text-center {
            text-align: center;
        }
        
        thead th.text-right {
            text-align: right;
        }
        
        tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        
        tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        
        tbody tr:hover {
            background: #f1f5f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .product-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .product-sku {
            font-size: 10px;
            color: #94a3b8;
        }
        
        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .totals-box {
            width: 280px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12px;
        }
        
        .total-row.subtotal {
            color: #64748b;
        }
        
        .total-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            border-top: 2px solid #a855f7;
            margin-top: 8px;
            padding-top: 12px;
        }
        
        .total-row.grand-total .amount {
            color: #a855f7;
        }
        
        /* Payment Info */
        .payment-section {
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .payment-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #7c3aed;
            margin-bottom: 10px;
        }
        
        .payment-grid {
            display: flex;
            gap: 30px;
        }
        
        .payment-item {
            flex: 1;
        }
        
        .payment-label {
            font-size: 10px;
            color: #64748b;
        }
        
        .payment-value {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .payment-value.pending {
            color: #dc2626;
        }
        
        .payment-value.paid {
            color: #16a34a;
        }
        
        /* Notes */
        .notes-section {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .notes-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #b45309;
            margin-bottom: 5px;
        }
        
        .notes-content {
            font-size: 11px;
            color: #78350f;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 10px;
        }
        
        .footer p {
            margin: 3px 0;
        }
        
        /* Print Actions */
        .print-actions {
            position: fixed;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
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
        
        @media print {
            .print-actions {
                display: none !important;
            }
            
            body {
                padding: 0;
            }
            
            .receipt {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Print Actions -->
    <div class="print-actions">
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button class="btn btn-close" onclick="window.close()">✕ Cerrar</button>
    </div>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">{{ $purchase->branch->name ?? 'MikPOS' }}</div>
                <div class="company-details">
                    @if($purchase->branch->tax_id)
                    <p><strong>NIT:</strong> {{ $purchase->branch->tax_id }}</p>
                    @endif
                    @if($purchase->branch->address)
                    <p>{{ $purchase->branch->address }}</p>
                    @endif
                    @if($purchase->branch->phone)
                    <p>Tel: {{ $purchase->branch->phone }}</p>
                    @endif
                </div>
            </div>
            <div class="document-info">
                <div class="document-title">ORDEN DE COMPRA</div>
                <div class="document-number">{{ $purchase->purchase_number }}</div>
                <div class="document-date">
                    Fecha: {{ $purchase->purchase_date->format('d/m/Y') }}<br>
                    @if($purchase->supplier_invoice)
                    Factura Proveedor: {{ $purchase->supplier_invoice }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Info Row -->
        <div class="info-row">
            <div class="info-box">
                <div class="info-box-title">Proveedor</div>
                <div class="info-box-content">
                    @if($purchase->supplier)
                    <p><strong>{{ $purchase->supplier->name }}</strong></p>
                    @if($purchase->supplier->tax_id)
                    <p>NIT: {{ $purchase->supplier->tax_id }}</p>
                    @endif
                    @if($purchase->supplier->phone)
                    <p>Tel: {{ $purchase->supplier->phone }}</p>
                    @endif
                    @if($purchase->supplier->email)
                    <p>{{ $purchase->supplier->email }}</p>
                    @endif
                    @else
                    <p>Sin proveedor asignado</p>
                    @endif
                </div>
            </div>
            <div class="info-box">
                <div class="info-box-title">Información de Compra</div>
                <div class="info-box-content">
                    <p><strong>Estado:</strong> {{ $purchase->getStatusLabel() }}</p>
                    <p><strong>Tipo de Pago:</strong> {{ $purchase->getPaymentTypeLabel() }}</p>
                    @if($purchase->paymentMethod)
                    <p><strong>Método:</strong> {{ $purchase->paymentMethod->name }}</p>
                    @endif
                    <p><strong>Registrado por:</strong> {{ $purchase->user->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="items-section">
            <div class="section-title">Detalle de Productos</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Producto</th>
                        <th class="text-center" style="width: 80px;">Cantidad</th>
                        <th class="text-right" style="width: 100px;">Costo Unit.</th>
                        <th class="text-right" style="width: 100px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="product-name">{{ $item->product->name ?? $item->product_name ?? 'Producto' }}</div>
                            <div class="product-sku">{{ $item->product->sku ?? '' }}</div>
                        </td>
                        <td class="text-center">{{ number_format($item->quantity, 0) }} {{ $item->product->unit->abbreviation ?? '' }}</td>
                        <td class="text-right">${{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row subtotal">
                    <span>Subtotal</span>
                    <span>${{ number_format($purchase->subtotal, 2) }}</span>
                </div>
                @if($purchase->tax_amount > 0)
                <div class="total-row subtotal">
                    <span>Impuestos</span>
                    <span>${{ number_format($purchase->tax_amount, 2) }}</span>
                </div>
                @endif
                @if($purchase->discount_amount > 0)
                <div class="total-row subtotal">
                    <span>Descuento</span>
                    <span>-${{ number_format($purchase->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="total-row grand-total">
                    <span>TOTAL</span>
                    <span class="amount">${{ number_format($purchase->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Info (for credit purchases) -->
        @if($purchase->payment_type === 'credit')
        <div class="payment-section">
            <div class="payment-title">Información de Crédito</div>
            <div class="payment-grid">
                <div class="payment-item">
                    <div class="payment-label">Monto a Crédito</div>
                    <div class="payment-value">${{ number_format($purchase->credit_amount, 2) }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Monto Pagado</div>
                    <div class="payment-value paid">${{ number_format($purchase->paid_amount, 2) }}</div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Saldo Pendiente</div>
                    <div class="payment-value {{ $purchase->getRemainingCredit() > 0 ? 'pending' : 'paid' }}">
                        ${{ number_format($purchase->getRemainingCredit(), 2) }}
                    </div>
                </div>
                <div class="payment-item">
                    <div class="payment-label">Estado de Pago</div>
                    <div class="payment-value">{{ $purchase->getPaymentStatusLabel() }}</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Notes -->
        @if($purchase->notes)
        <div class="notes-section">
            <div class="notes-title">Notas</div>
            <div class="notes-content">{{ $purchase->notes }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>{{ $purchase->branch->name ?? 'MikPOS' }} - Sistema de Punto de Venta</p>
        </div>
    </div>
</body>
</html>
