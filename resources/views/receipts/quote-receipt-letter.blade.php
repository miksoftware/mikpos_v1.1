<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $quote->quote_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: letter; margin: 15mm; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color: #1e293b; padding: 20px; }
        .container { max-width: 720px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 16px; border-bottom: 3px solid #ff7261; margin-bottom: 20px; }
        .header-left { flex: 1; }
        .header-right { text-align: right; }
        .business-name { font-size: 22px; font-weight: bold; color: #1a1225; margin-bottom: 4px; }
        .business-info { font-size: 10px; color: #64748b; line-height: 1.6; }
        .quote-title { font-size: 24px; font-weight: bold; color: #ff7261; }
        .quote-number { font-size: 14px; color: #475569; margin-top: 4px; }
        .quote-date { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .info-block { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .info-label { font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
        .info-value { font-size: 12px; color: #1e293b; font-weight: 600; }
        .info-sub { font-size: 10px; color: #64748b; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead { background: linear-gradient(135deg, #ff7261, #a855f7); color: #fff; }
        thead th { padding: 10px 8px; font-size: 10px; font-weight: bold; text-transform: uppercase; text-align: left; }
        thead th.right { text-align: right; }
        tbody td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tbody td.right { text-align: right; }
        .totals { margin-left: auto; width: 280px; }
        .totals .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
        .totals .total-row { padding-top: 8px; border-top: 2px solid #1a1225; margin-top: 4px; font-size: 16px; font-weight: bold; color: #ff7261; }
        .validity-box { margin-top: 20px; padding: 12px; border: 2px dashed #ff7261; border-radius: 8px; text-align: center; }
        .validity-label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .validity-date { font-size: 18px; font-weight: bold; color: #1e293b; margin-top: 4px; }
        .notes-box { margin-top: 16px; padding: 12px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; font-size: 11px; color: #78350f; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
        .print-actions { position: fixed; top: 10px; right: 10px; display: flex; gap: 8px; z-index: 100; }
        .btn { padding: 10px 20px; font-size: 13px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
        .btn-print { background: linear-gradient(135deg, #ff7261, #a855f7); color: white; }
        .btn-close { background: #6b7280; color: white; }
        @media print { .no-print { display: none !important; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button class="btn btn-close" onclick="window.close()">✕ Cerrar</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="business-name">{{ $quote->branch->name }}</div>
                <div class="business-info">
                    @if($quote->branch->tax_id)NIT: {{ $quote->branch->tax_id }}<br>@endif
                    @if($quote->branch->address){{ $quote->branch->address }}<br>@endif
                    @if($quote->branch->municipality){{ $quote->branch->municipality->name }}@if($quote->branch->department), {{ $quote->branch->department->name }}@endif<br>@endif
                    @if($quote->branch->phone)Tel: {{ $quote->branch->phone }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="quote-title">COTIZACIÓN</div>
                <div class="quote-number">{{ $quote->quote_number }}</div>
                <div class="quote-date">{{ $quote->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <div class="info-label">Cliente</div>
                @if($quote->customer)
                    <div class="info-value">{{ $quote->customer->customer_type === 'juridico' ? $quote->customer->business_name : trim($quote->customer->first_name . ' ' . $quote->customer->last_name) }}</div>
                    <div class="info-sub">{{ $quote->customer->taxDocument->abbreviation ?? 'Doc' }}: {{ $quote->customer->document_number ?? '—' }}</div>
                    @if($quote->customer->phone)<div class="info-sub">Tel: {{ $quote->customer->phone }}</div>@endif
                    @if($quote->customer->email)<div class="info-sub">{{ $quote->customer->email }}</div>@endif
                @else
                    <div class="info-value">Consumidor Final</div>
                @endif
            </div>
            <div class="info-block">
                <div class="info-label">Información</div>
                <div class="info-sub">Vendedor: <strong>{{ $quote->user->name ?? 'N/A' }}</strong></div>
                <div class="info-sub">Sucursal: <strong>{{ $quote->branch->name }}</strong></div>
                <div class="info-sub">Estado: <strong>{{ ucfirst($quote->status) }}</strong></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="right">Cant.</th>
                    <th class="right">P. Unit.</th>
                    <th class="right">Desc.</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                @php
                    $itemPriceWithTax = $item->tax_rate > 0 ? $item->unit_price * (1 + $item->tax_rate / 100) : $item->unit_price;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->product_sku)<br><span style="font-size:9px;color:#94a3b8">{{ $item->product_sku }}</span>@endif
                    </td>
                    <td class="right">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                    <td class="right">${{ number_format($itemPriceWithTax, 2) }}</td>
                    <td class="right">{{ $item->discount_amount > 0 ? '-$' . number_format($item->discount_amount, 2) : '—' }}</td>
                    <td class="right"><strong>${{ number_format($item->total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>Subtotal</span><span>${{ number_format($quote->subtotal, 2) }}</span></div>
            @if($quote->tax_total > 0)
            <div class="row"><span>IVA</span><span>${{ number_format($quote->tax_total, 2) }}</span></div>
            @endif
            @if($quote->discount > 0)
            <div class="row"><span>Descuentos</span><span style="color:#ea580c">-${{ number_format($quote->discount, 2) }}</span></div>
            @endif
            <div class="row total-row"><span>TOTAL</span><span>${{ number_format($quote->total, 2) }}</span></div>
        </div>

        @if($quote->valid_until)
        <div class="validity-box">
            <div class="validity-label">Cotización válida hasta</div>
            <div class="validity-date">{{ $quote->valid_until->format('d \d\e F \d\e Y') }}</div>
        </div>
        @endif

        @if($quote->notes)
        <div class="notes-box">
            <strong>Observaciones:</strong> {{ $quote->notes }}
        </div>
        @endif

        <div class="footer">
            Esta cotización no es una factura ni constituye obligación de venta.<br>
            {{ $quote->branch->name }} · {{ now()->format('d/m/Y H:i') }}
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
