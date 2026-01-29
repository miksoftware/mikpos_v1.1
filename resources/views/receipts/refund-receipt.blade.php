<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=80mm, initial-scale=1.0">
    <title>Devolución {{ $refund->number }}</title>
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
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            width: 80mm;
            padding: 3mm;
            background: white;
            color: #000;
        }
        
        .header {
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 1px dashed #000;
            margin-bottom: 8px;
        }
        
        .branch-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .branch-info {
            font-size: 10px;
            color: #333;
        }
        
        .document-type {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            margin: 10px 0;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .refund-number {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0;
        }
        
        .section {
            margin: 8px 0;
            padding: 6px 0;
            border-bottom: 1px dashed #ccc;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
            color: #666;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin: 2px 0;
        }
        
        .info-label {
            color: #666;
        }
        
        .info-value {
            font-weight: bold;
            text-align: right;
        }
        
        .original-sale {
            background: #f3f4f6;
            padding: 6px;
            margin: 8px 0;
            border-radius: 4px;
        }
        
        .original-sale-title {
            font-size: 10px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .items-table {
            width: 100%;
            margin: 8px 0;
        }
        
        .items-header {
            font-size: 10px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }
        
        .items-header-row {
            display: flex;
            justify-content: space-between;
        }
        
        .item {
            padding: 4px 0;
            border-bottom: 1px dotted #ddd;
        }
        
        .item-name {
            font-size: 11px;
            font-weight: bold;
        }
        
        .item-sku {
            font-size: 9px;
            color: #666;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin-top: 2px;
        }
        
        .totals {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 2px solid #000;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin: 3px 0;
        }
        
        .total-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            padding-top: 6px;
            margin-top: 6px;
            border-top: 1px dashed #000;
            color: #dc2626;
        }
        
        .reason-section {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 8px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .reason-title {
            font-size: 10px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 4px;
        }
        
        .reason-text {
            font-size: 11px;
            color: #333;
        }
        
        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #000;
        }
        
        .footer-text {
            font-size: 10px;
            color: #666;
            margin: 4px 0;
        }
        
        .signature-line {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #000;
            text-align: center;
        }
        
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                width: 80mm;
                padding: 2mm;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="branch-name">{{ $refund->sale->branch->name }}</div>
        <div class="branch-info">
            @if($refund->sale->branch->address)
                {{ $refund->sale->branch->address }}<br>
            @endif
            @if($refund->sale->branch->municipality)
                {{ $refund->sale->branch->municipality->name }}, {{ $refund->sale->branch->department->name ?? '' }}<br>
            @endif
            @if($refund->sale->branch->phone)
                Tel: {{ $refund->sale->branch->phone }}<br>
            @endif
            @if($refund->sale->branch->nit)
                NIT: {{ $refund->sale->branch->nit }}
            @endif
        </div>
    </div>

    <!-- Document Type Banner -->
    <div class="document-type">
        DEVOLUCIÓN {{ $refund->type === 'total' ? 'TOTAL' : 'PARCIAL' }}
    </div>

    <!-- Refund Number -->
    <div class="refund-number">
        No. {{ $refund->number }}
    </div>

    <!-- Date and User Info -->
    <div class="section">
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $refund->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Atendido por:</span>
            <span class="info-value">{{ $refund->user->name ?? 'N/A' }}</span>
        </div>
    </div>

    <!-- Original Sale Reference -->
    <div class="original-sale">
        <div class="original-sale-title">FACTURA ORIGINAL</div>
        <div class="info-row">
            <span class="info-label">Número:</span>
            <span class="info-value">{{ $refund->sale->invoice_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $refund->sale->created_at->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total Original:</span>
            <span class="info-value">${{ number_format($refund->sale->total, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="section">
        <div class="section-title">Cliente</div>
        @if($refund->sale->customer)
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value">{{ $refund->sale->customer->full_name }}</span>
            </div>
            @if($refund->sale->customer->document_number)
            <div class="info-row">
                <span class="info-label">{{ $refund->sale->customer->taxDocument->abbreviation ?? 'Doc' }}:</span>
                <span class="info-value">{{ $refund->sale->customer->document_number }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Tipo:</span>
                <span class="info-value">{{ $refund->sale->customer->customer_type === 'juridico' ? 'Persona Jurídica' : 'Persona Natural' }}</span>
            </div>
        @else
            <div class="info-row">
                <span class="info-value">Consumidor Final</span>
            </div>
        @endif
    </div>

    <!-- Refund Reason -->
    <div class="reason-section">
        <div class="reason-title">MOTIVO DE DEVOLUCIÓN</div>
        <div class="reason-text">{{ $refund->reason }}</div>
    </div>

    <!-- Items -->
    <div class="items-table">
        <div class="items-header">
            <div class="items-header-row">
                <span>PRODUCTOS DEVUELTOS</span>
            </div>
        </div>
        
        @foreach($refund->items as $item)
        <div class="item">
            <div class="item-name">{{ $item->product_name }}</div>
            @if($item->product_sku)
            <div class="item-sku">SKU: {{ $item->product_sku }}</div>
            @endif
            <div class="item-details">
                <span>{{ $item->quantity }} x ${{ number_format($item->unit_price, 0, ',', '.') }}</span>
                <span>${{ number_format($item->total, 0, ',', '.') }}</span>
            </div>
            @if($item->quantity < $item->original_quantity)
            <div class="item-sku">
                ({{ $item->quantity }} de {{ $item->original_quantity }} unidades)
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Totals -->
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>${{ number_format($refund->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($refund->tax_total > 0)
        <div class="total-row">
            <span>IVA:</span>
            <span>${{ number_format($refund->tax_total, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span>TOTAL DEVOLUCIÓN:</span>
            <span>${{ number_format($refund->total, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Signature Lines -->
    <div class="signature-line">
        <div class="signature-label">Firma del Cliente</div>
    </div>
    
    <div class="signature-line">
        <div class="signature-label">Firma del Responsable</div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-text">
            Este documento es un comprobante de devolución
        </div>
        <div class="footer-text">
            Conserve este documento para cualquier reclamo
        </div>
        <div class="footer-text">
            {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
