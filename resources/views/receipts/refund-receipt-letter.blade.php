@php
    $options = App\Models\PrintFormatSetting::getLetterOptions('refund');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolución {{ $refund->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: letter; margin: 15mm 20mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000;
            background: #fff;
            -webkit-print-color-adjust: exact;
        }
        .invoice { max-width: 720px; margin: 0 auto; padding: 20px; }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 15px;
            border-bottom: 2px solid #dc2626;
            margin-bottom: 15px;
        }
        .header-title-box { display: flex; flex-direction: column; }
        .invoice-title { font-size: 24px; font-weight: bold; letter-spacing: 1px; color: #dc2626; text-transform: uppercase; }
        .badge-type {
            display: inline-block;
            align-self: flex-start;
            background: #dc2626;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
            text-transform: uppercase;
        }
        .invoice-number-box { text-align: right; }
        .invoice-number-value { font-size: 16px; font-weight: bold; color: #111827; }
        .invoice-date { font-size: 11px; color: #555; margin-top: 4px; }
        
        .info-row {
            display: flex;
            gap: 20px;
            margin-bottom: 18px;
        }
        .info-col { flex: 1; }
        .info-label {
            font-size: 10px;
            color: #777;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
            border-bottom: 1px solid #eee;
            padding-bottom: 2px;
        }
        .info-value { font-size: 11px; color: #333; line-height: 1.6; }
        .info-value strong { color: #000; }
        
        .reason-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #dc2626;
            padding: 10px 14px;
            margin-bottom: 18px;
            border-radius: 6px;
        }
        .reason-title { font-size: 10px; font-weight: bold; color: #dc2626; text-transform: uppercase; margin-bottom: 2px; }
        .reason-text { font-size: 11px; color: #1f2937; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 8px 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #991b1b;
        }
        .items-table thead th.text-center { text-align: center; }
        .items-table thead th.text-right { text-align: right; }
        .items-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            font-size: 11px;
            vertical-align: top;
        }
        .items-table tbody td.text-center { text-align: center; }
        .items-table tbody td.text-right { text-align: right; }
        .items-table tbody tr:nth-child(even) { background: #fafafa; }
        
        .totals-section { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .payment-info { font-size: 11px; color: #444; line-height: 1.8; }
        .payment-info strong { color: #000; }
        .totals-box { width: 280px; background: #f8f8f8; border: 1px solid #ddd; padding: 10px 15px; }
        .total-row { display: flex; justify-content: space-between; font-size: 11px; padding: 3px 0; color: #444; }
        .total-row.grand-total {
            font-size: 16px; font-weight: bold; color: #dc2626;
            border-top: 2px solid #333; margin-top: 6px; padding-top: 8px;
        }
        .amount-words {
            font-size: 10px; color: #555; font-style: italic; margin-bottom: 20px;
            padding: 6px 10px; background: #f8f8f8; border-left: 3px solid #ccc;
        }
        
        .signature-grid {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        .signature-box {
            width: 200px;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 6px;
            font-size: 11px;
            font-weight: bold;
        }

        .invoice-footer {
            text-align: center; padding-top: 15px; border-top: 1px solid #ddd;
            font-size: 11px; color: #777;
        }
        .footer-thanks { font-size: 13px; font-weight: bold; color: #333; margin-bottom: 4px; }
        .seller-info { font-size: 10px; color: #888; margin-top: 6px; }

        .print-actions { position: fixed; top: 10px; right: 10px; display: flex; gap: 8px; z-index: 100; }
        .btn { padding: 10px 20px; font-size: 13px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
        .btn-print { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .btn-close { background: #6b7280; color: white; }

        @media print {
            body { padding: 0; }
            .invoice { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="print-actions no-print">
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button class="btn btn-close" onclick="window.close()">✕ Cerrar</button>
    </div>

    <div class="invoice">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-title-box">
                @if($options['show_logo'] && $refund->sale->branch->logo)
                <div style="margin-bottom: 10px;">
                    <img src="{{ Storage::url($refund->sale->branch->logo) }}" alt="{{ $refund->sale->branch->name }}" style="max-width: 250px; max-height: 100px; object-fit: contain;">
                </div>
                @endif
                <div class="invoice-title">COMPROBANTE DE DEVOLUCIÓN</div>
                <div class="badge-type">DEVOLUCIÓN {{ $refund->type === 'total' ? 'TOTAL' : 'PARCIAL' }}</div>
            </div>
            <div class="invoice-number-box">
                <div class="invoice-number-value">No. {{ $refund->number }}</div>
                <div class="invoice-date">Fecha: {{ $refund->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- Info Row: Business + Customer + Refund Info -->
        <div class="info-row">
            @if($options['show_business'])
            <div class="info-col">
                <div class="info-label">Negocio</div>
                <div class="info-value">
                    <strong>{{ $refund->sale->branch->name }}</strong><br>
                    @if($refund->sale->branch->nit)
                        NIT: {{ $refund->sale->branch->nit }}<br>
                    @elseif($refund->sale->branch->tax_id)
                        NIT: {{ $refund->sale->branch->tax_id }}<br>
                    @endif
                    @if($refund->sale->branch->address)
                        {{ $refund->sale->branch->address }}
                        @if($refund->sale->branch->municipality), {{ $refund->sale->branch->municipality->name }}@endif
                        <br>
                    @endif
                    @if($refund->sale->branch->phone)
                        Tel: {{ $refund->sale->branch->phone }}<br>
                    @endif
                    @if($refund->sale->branch->email)
                        {{ $refund->sale->branch->email }}
                    @endif
                </div>
            </div>
            @endif

            @if($options['show_customer'])
            <div class="info-col">
                <div class="info-label">Cliente</div>
                <div class="info-value">
                    @if($refund->sale->customer)
                        <strong>{{ $refund->sale->customer->full_name }}</strong><br>
                        @if($refund->sale->customer->document_number)
                            {{ $refund->sale->customer->taxDocument->abbreviation ?? 'Doc' }}: {{ $refund->sale->customer->document_number }}<br>
                        @endif
                        @if($refund->sale->customer->phone)
                            Tel: {{ $refund->sale->customer->phone }}<br>
                        @endif
                        @if($refund->sale->customer->department || $refund->sale->customer->municipality)
                            {{ $refund->sale->customer->municipality?->name }}@if($refund->sale->customer->department && $refund->sale->customer->municipality), @endif{{ $refund->sale->customer->department?->name }}<br>
                        @endif
                        @if($refund->sale->customer->address)
                            <strong>Dir:</strong> {{ $refund->sale->customer->address }}<br>
                        @endif
                        @if($refund->sale->customer->email)
                            {{ $refund->sale->customer->email }}
                        @endif
                    @else
                        <strong>Consumidor Final</strong>
                    @endif
                </div>
            </div>
            @endif

            @if($options['show_sale_info'])
            <div class="info-col">
                <div class="info-label">Factura Original & Info</div>
                <div class="info-value">
                    <strong>Factura:</strong> {{ $refund->sale->invoice_number }}<br>
                    <strong>Fecha Factura:</strong> {{ $refund->sale->created_at->format('d/m/Y') }}<br>
                    <strong>Total Factura:</strong> ${{ number_format($refund->sale->total, 0, ',', '.') }}<br>
                    <strong>Atendido por:</strong> {{ $refund->user->name ?? 'N/A' }}
                </div>
            </div>
            @endif
        </div>

        <!-- Refund Reason -->
        <div class="reason-box">
            <div class="reason-title">MOTIVO DE DEVOLUCIÓN</div>
            <div class="reason-text">{{ $refund->reason }}</div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>PRODUCTOS DEVUELTOS</th>
                    <th style="width: 110px;" class="text-center">CANTIDAD</th>
                    <th style="width: 100px;" class="text-right">PRECIO UNIT.</th>
                    <th style="width: 110px;" class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($refund->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->product_sku)
                            <br><small style="color: #666;">SKU: {{ $item->product_sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $item->quantity }}
                        @if($item->quantity < $item->original_quantity)
                            <br><small style="color: #666;">(de {{ $item->original_quantity }} orig.)</small>
                        @endif
                    </td>
                    <td class="text-right">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            @if($options['show_payment_info'])
            <div class="payment-info">
                <strong>Comprobante de Devolución</strong><br>
                <strong>Emitida:</strong> {{ $refund->created_at->format('d/m/Y H:i:s') }}<br>
                <strong>Estado:</strong> Aplicado
            </div>
            @else
            <div></div>
            @endif

            <div class="totals-box">
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
        </div>

        <!-- Amount in words -->
        @if($options['show_amount_words'])
        @php
            $intPart = intval($refund->total);
            $decPart = round(($refund->total - $intPart) * 100);
            $formatter = new \NumberFormatter('es_CO', \NumberFormatter::SPELLOUT);
            $words = mb_strtoupper($formatter->format($intPart));
        @endphp
        <div class="amount-words">
            Monto en letras: {{ $words }} CON {{ str_pad($decPart, 2, '0', STR_PAD_LEFT) }}/100
        </div>
        @endif

        <!-- Signatures -->
        <div class="signature-grid">
            <div class="signature-box">
                Firma del Cliente
            </div>
            <div class="signature-box">
                Firma del Responsable
            </div>
        </div>

        <!-- Footer -->
        @if($options['show_footer'])
        <div class="invoice-footer">
            <div class="footer-thanks">Este documento es un comprobante de devolución</div>
            <div>Conserve este documento para cualquier reclamo</div>
            <div class="seller-info">
                {{ $refund->sale->branch->name }} | {{ now()->format('d/m/Y H:i:s') }}
            </div>
        </div>
        @endif
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
