<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=72mm">
    <title>Cotización #{{ $quote->quote_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 72mm auto; margin: 0mm; }
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
        .receipt { width: 100%; }
        .header { text-align: center; padding-bottom: 6px; border-bottom: 1px dashed #000; margin-bottom: 6px; }
        .business-name { font-size: 16px; font-weight: bold; text-transform: uppercase; word-wrap: break-word; }
        .business-info { font-size: 10px; }
        .business-info p { margin: 1px 0; }
        .invoice-info { text-align: center; padding: 6px 0; border-bottom: 1px dashed #000; margin-bottom: 6px; }
        .invoice-number { font-size: 13px; font-weight: bold; margin-bottom: 2px; }
        .invoice-type { display: inline-block; padding: 1px 6px; background: #000; color: #fff; font-size: 9px; font-weight: bold; margin-bottom: 3px; }
        .date-time { font-size: 10px; }
        .customer-section { padding: 6px 0; border-bottom: 1px dashed #000; margin-bottom: 6px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }
        .customer-type-badge { display: inline-block; padding: 1px 4px; background: #ddd; font-size: 9px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
        .customer-name { font-weight: bold; font-size: 11px; word-wrap: break-word; }
        .customer-doc { font-size: 10px; }
        .items-section { margin-bottom: 6px; }
        .items-header { display: flex; justify-content: space-between; font-size: 9px; font-weight: bold; text-transform: uppercase; padding-bottom: 3px; border-bottom: 1px solid #000; margin-bottom: 4px; }
        .item { margin-bottom: 4px; padding-bottom: 3px; border-bottom: 1px dotted #000; }
        .item:last-child { border-bottom: none; }
        .item-name { font-weight: bold; font-size: 10px; word-wrap: break-word; overflow-wrap: break-word; }
        .item-details { display: flex; justify-content: space-between; font-size: 11px; }
        .item-total { font-weight: bold; font-size: 12px; white-space: nowrap; }
        .totals-section { border-top: 1px solid #000; padding-top: 6px; margin-bottom: 6px; }
        .total-row { display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; margin-bottom: 2px; }
        .total-row.grand-total { font-size: 16px; font-weight: bold; padding-top: 4px; border-top: 1px dashed #000; margin-top: 4px; }
        .validity-section { padding: 6px 4px; border: 1px solid #000; margin-bottom: 6px; text-align: center; }
        .validity-title { font-size: 10px; font-weight: bold; margin-bottom: 4px; }
        .validity-date { font-size: 13px; font-weight: bold; }
        .seller-section { font-size: 10px; text-align: center; margin-bottom: 6px; padding: 4px 0; }
        .seller-section p { margin: 1px 0; }
        .footer { text-align: center; padding-top: 6px; border-top: 1px dashed #000; }
        .thank-you { font-size: 14px; font-weight: bold; margin-bottom: 3px; }
        .footer-message { font-size: 10px; margin-bottom: 6px; }
        .powered-by { font-size: 9px; color: #000; margin-top: 6px; padding-top: 4px; border-top: 1px dotted #000; }
        @media print { body { width: 72mm; max-width: 72mm; padding: 1mm; } .no-print { display: none !important; } }
        .print-actions { position: fixed; top: 10px; right: 10px; display: flex; gap: 8px; z-index: 100; }
        .btn { padding: 10px 20px; font-size: 13px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
        .btn-print { background: linear-gradient(135deg, #ff7261, #a855f7); color: white; }
        .btn-close { background: #6b7280; color: white; }
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
            @if($showLogo && $quote->branch->logo)
            <div style="text-align:center; margin-bottom:8px;">
                <img src="{{ Storage::url($quote->branch->logo) }}" alt="{{ $quote->branch->name }}" style="max-width:220px; max-height:110px; width:90%; object-fit:contain; display:inline-block;">
            </div>
            @endif
            <div class="business-name">{{ $quote->branch->name }}</div>
            <div class="business-info">
                @if($quote->branch->tax_id)<p><strong>NIT:</strong> {{ $quote->branch->tax_id }}</p>@endif
                @if($quote->branch->address)<p>{{ $quote->branch->address }}</p>@endif
                @if($quote->branch->municipality)<p>{{ $quote->branch->municipality->name }}@if($quote->branch->department), {{ $quote->branch->department->name }}@endif</p>@endif
                @if($quote->branch->phone)<p>Tel: {{ $quote->branch->phone }}</p>@endif
            </div>
        </div>

        <!-- Quote Info -->
        <div class="invoice-info">
            <span class="invoice-type">COTIZACIÓN</span>
            <div class="invoice-number">{{ $quote->quote_number }}</div>
            <div class="date-time">{{ $quote->created_at->format('d/m/Y H:i') }}</div>
        </div>

        <!-- Customer -->
        <div class="customer-section">
            <div class="section-title">Cliente</div>
            @if($quote->customer)
            <span class="customer-type-badge">{{ $quote->customer->customer_type === 'juridico' ? 'Persona Jurídica' : 'Persona Natural' }}</span>
            <div class="customer-name">
                {{ $quote->customer->customer_type === 'juridico' ? $quote->customer->business_name : trim($quote->customer->first_name . ' ' . $quote->customer->last_name) }}
            </div>
            @if($quote->customer->document_number)
                <div class="customer-doc">{{ $quote->customer->taxDocument->abbreviation ?? 'Doc' }}: {{ $quote->customer->document_number }}</div>
            @endif
            @if($quote->customer->phone)
                <div class="customer-doc">Tel: {{ $quote->customer->phone }}</div>
            @endif
            @else
            <div class="customer-name">Consumidor Final</div>
            @endif
        </div>

        <!-- Items -->
        <div class="items-section">
            <div class="section-title">Detalle</div>
            <div class="items-header"><span>Producto</span><span>Total</span></div>
            @foreach($quote->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->product_name }}</div>
                @php
                    $itemPriceWithTax = $item->tax_rate > 0 ? $item->unit_price * (1 + $item->tax_rate / 100) : $item->unit_price;
                @endphp
                <div class="item-details">
                    <span>{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }} x ${{ number_format($itemPriceWithTax, 0) }}</span>
                    <span class="item-total">${{ number_format($item->total, 0) }}</span>
                </div>
                @if($item->discount_amount > 0)
                <div class="item-details" style="font-size: 9px;">
                    <span>Desc: {{ $item->discount_type === 'percentage' ? $item->discount_type_value . '%' : '$' . number_format($item->discount_type_value, 0) }}</span>
                    <span>-${{ number_format($item->discount_amount, 0) }}</span>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row"><span>Subtotal</span><span>${{ number_format($quote->subtotal, 0) }}</span></div>
            @if($quote->tax_total > 0)
            <div class="total-row"><span>IVA</span><span>${{ number_format($quote->tax_total, 0) }}</span></div>
            @endif
            @php
                $itemDiscounts = $quote->discount - ($quote->global_discount_amount ?? 0);
            @endphp
            @if($itemDiscounts > 0)
            <div class="total-row"><span>Descuento{{ ($quote->global_discount_amount ?? 0) > 0 ? ' (items)' : '' }}</span><span>-${{ number_format($itemDiscounts, 0) }}</span></div>
            @endif
            @if(($quote->global_discount_amount ?? 0) > 0)
            <div class="total-row"><span>Desc. factura{{ $quote->global_discount_type === 'percentage' ? ' (' . rtrim(rtrim(number_format($quote->global_discount_value, 2), '0'), '.') . '%)' : '' }}</span><span>-${{ number_format($quote->global_discount_amount, 0) }}</span></div>
            @endif
            <div class="total-row grand-total"><span>TOTAL</span><span>${{ number_format($quote->total, 0) }}</span></div>
        </div>

        <!-- Validity -->
        @if($quote->valid_until)
        <div class="validity-section">
            <div class="validity-title">VÁLIDA HASTA</div>
            <div class="validity-date">{{ $quote->valid_until->format('d/m/Y') }}</div>
        </div>
        @endif

        @if($quote->notes)
        <div class="customer-section">
            <div class="section-title">Notas</div>
            <div class="customer-doc">{{ $quote->notes }}</div>
        </div>
        @endif

        <!-- Seller -->
        <div class="seller-section">
            <p><strong>Atendido por:</strong> {{ $quote->user->name ?? 'N/A' }}</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">¡Gracias por su preferencia!</div>
            <div class="footer-message">
                @if($quote->branch->receipt_header)
                {{ $quote->branch->receipt_header }}
                @else
                Esta cotización no es una factura ni constituye obligación de venta
                @endif
            </div>
            <div class="powered-by">
                {{ $quote->branch->name }}<br>
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'auto') {
                setTimeout(function() { window.print(); }, 500);
            }
        };
    </script>
</body>
</html>
