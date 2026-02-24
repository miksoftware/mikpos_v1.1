@php
    $options = App\Models\PrintFormatSetting::getLetterOptions('pos');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #{{ $sale->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: letter; margin: 10mm 15mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            -webkit-print-color-adjust: exact;
        }
        .invoice { max-width: 720px; margin: 0 auto; padding: 10px; }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 8px;
            border-bottom: 2px solid #333;
            margin-bottom: 8px;
        }
        .invoice-title { font-size: 22px; font-weight: bold; letter-spacing: 2px; }
        .invoice-number-box { text-align: right; }
        .invoice-number-value { font-size: 12px; font-weight: bold; color: #0066cc; }
        .invoice-date { font-size: 9px; color: #555; margin-top: 2px; }
        .info-row {
            display: flex;
            gap: 15px;
            margin-bottom: 8px;
        }
        .info-col { flex: 1; }
        .info-label {
            font-size: 8px;
            color: #777;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
            border-bottom: 1px solid #eee;
            padding-bottom: 1px;
        }
        .info-value { font-size: 9.5px; color: #333; line-height: 1.5; }
        .info-value strong { color: #000; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .items-table thead th {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 3px 5px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
        }
        .items-table thead th.text-center { text-align: center; }
        .items-table thead th.text-right { text-align: right; }
        .items-table tbody td {
            border: 1px solid #ddd;
            padding: 2px 5px;
            font-size: 9px;
            vertical-align: top;
        }
        .items-table tbody td.text-center { text-align: center; }
        .items-table tbody td.text-right { text-align: right; }
        .items-table tbody tr:nth-child(even) { background: #fafafa; }
        .totals-section { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .payment-info { font-size: 9px; color: #444; line-height: 1.6; }
        .payment-info strong { color: #000; }
        .totals-box { width: 260px; background: #f8f8f8; border: 1px solid #ddd; padding: 6px 10px; }
        .total-row { display: flex; justify-content: space-between; font-size: 9px; padding: 2px 0; color: #444; }
        .total-row.grand-total {
            font-size: 13px; font-weight: bold; color: #0066cc;
            border-top: 2px solid #333; margin-top: 4px; padding-top: 5px;
        }
        .amount-words {
            font-size: 9px; color: #555; font-style: italic; margin-bottom: 8px;
            padding: 4px 8px; background: #f8f8f8; border-left: 3px solid #ccc;
        }
        .dian-section {
            border: 1px solid #ccc; padding: 6px; margin-bottom: 8px;
            text-align: center; font-size: 9px;
        }
        .dian-title { font-weight: bold; margin-bottom: 2px; }
        .cufe { font-size: 7px; word-break: break-all; color: #555; margin: 2px 0; }
        .qr-container img { max-width: 80px; height: auto; }
        .invoice-footer {
            text-align: center; padding-top: 8px; border-top: 1px solid #ddd;
            font-size: 9px; color: #777;
        }
        .footer-thanks { font-size: 12px; font-weight: bold; color: #333; margin-bottom: 2px; }
        .seller-info { font-size: 9px; color: #888; margin-top: 4px; }
        .print-actions { position: fixed; top: 10px; right: 10px; display: flex; gap: 8px; z-index: 100; }
        .btn { padding: 10px 20px; font-size: 13px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
        .btn-print { background: linear-gradient(135deg, #ff7261, #a855f7); color: white; }
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
            <div>
                <div class="invoice-title">FACTURA</div>
            </div>
            <div class="invoice-number-box">
                <div class="invoice-number-value">
                    @if($sale->is_electronic && $sale->cufe)
                        {{ $sale->dian_number ?? $sale->invoice_number }}
                    @else
                        {{ $sale->invoice_number }}
                    @endif
                </div>
                <div class="invoice-date">{{ $sale->created_at->format('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Info Row: Business + Customer + Sale Info in 3 columns -->
        <div class="info-row">
            @if($options['show_business'])
            <div class="info-col">
                <div class="info-label">Negocio</div>
                <div class="info-value">
                    <strong>{{ $sale->branch->name }}</strong><br>
                    @if($sale->branch->tax_id)
                        NIT: {{ $sale->branch->tax_id }}<br>
                    @endif
                    @if($sale->branch->address)
                        {{ $sale->branch->address }}
                        @if($sale->branch->municipality), {{ $sale->branch->municipality->name }}@endif
                        <br>
                    @endif
                    @if($sale->branch->phone)
                        Tel: {{ $sale->branch->phone }}<br>
                    @endif
                    @if($sale->branch->email)
                        {{ $sale->branch->email }}
                    @endif
                </div>
            </div>
            @endif

            @if($options['show_customer'])
            <div class="info-col">
                <div class="info-label">Cliente</div>
                <div class="info-value">
                    @if($sale->customer)
                        <strong>{{ $sale->customer->full_name }}</strong><br>
                        @if($sale->customer->document_number)
                            {{ $sale->customer->taxDocument->abbreviation ?? 'Doc' }}: {{ $sale->customer->document_number }}<br>
                        @endif
                        @if($sale->customer->phone)
                            Tel: {{ $sale->customer->phone }}<br>
                        @endif
                        @if($sale->customer->address)
                            {{ $sale->customer->address }}@if($sale->customer->municipality), {{ $sale->customer->municipality->name }}@endif<br>
                        @endif
                        @if($sale->customer->email)
                            {{ $sale->customer->email }}
                        @endif
                    @else
                        <strong>Consumidor Final</strong>
                    @endif
                </div>
            </div>
            @endif

            @if($options['show_sale_info'])
            <div class="info-col">
                <div class="info-label">Información de Venta</div>
                <div class="info-value">
                    @if($sale->is_electronic && $sale->cufe)
                        <strong>Tipo:</strong> Factura Electrónica<br>
                    @else
                        <strong>Tipo:</strong> Documento POS<br>
                    @endif
                    <strong>Fecha:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}<br>
                    <strong>Vendedor:</strong> {{ $sale->user->name }}
                    @if($sale->cashReconciliation && $sale->cashReconciliation->cashRegister)
                        <br><strong>Caja:</strong> {{ $sale->cashReconciliation->cashRegister->name }}
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>DESCRIPCIÓN</th>
                    <th style="width: 80px;" class="text-center">CANTIDAD</th>
                    <th style="width: 100px;" class="text-right">PRECIO</th>
                    <th style="width: 110px;" class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product_name }}
                        @if($item->discount_amount > 0)
                            @php
                                $discLabel = $item->discount_type === 'percentage' ? $item->discount_type_value . '%' : '$' . number_format($item->discount_type_value, 0);
                            @endphp
                            <br><small style="color: #888;">Desc: {{ $discLabel }}
                            @if($item->discount_reason) ({{ $item->discount_reason }}) @endif
                            = -${{ number_format($item->discount_amount, 0) }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            @if($options['show_payment_info'])
            <div class="payment-info">
                <strong>Condición de pago:</strong>
                @if($sale->payment_type === 'credit')
                    CRÉDITO
                    @if($sale->payment_due_date)
                        <br><strong>Vence:</strong> {{ $sale->payment_due_date->format('d/m/Y') }}
                    @endif
                @else
                    CONTADO
                @endif
                <br><strong>Emitida:</strong> {{ $sale->created_at->format('d/m/Y H:i:s') }}

                @if($sale->payments->count() > 0)
                    <br><br><strong>Forma de pago:</strong>
                    @foreach($sale->payments as $payment)
                        <br>{{ $payment->paymentMethod->name }}: ${{ number_format($payment->amount, 2) }}
                    @endforeach
                    @php $totalPaid = $sale->payments->sum('amount'); @endphp
                    @if($totalPaid > $sale->total)
                        <br><strong>Cambio:</strong> ${{ number_format($totalPaid - $sale->total, 2) }}
                    @endif
                @endif
            </div>
            @else
            <div></div>
            @endif
            <div class="totals-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>${{ number_format($sale->subtotal, 2) }}</span>
                </div>
                @if($sale->tax_total > 0)
                <div class="total-row">
                    <span>IVA:</span>
                    <span>${{ number_format($sale->tax_total, 2) }}</span>
                </div>
                @endif
                @if($sale->discount > 0)
                <div class="total-row">
                    <span>Descuento:</span>
                    <span>-${{ number_format($sale->discount, 2) }}</span>
                </div>
                @endif
                <div class="total-row grand-total">
                    <span>TOTAL:</span>
                    <span>${{ number_format($sale->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Amount in words -->
        @if($options['show_amount_words'])
        @php
            $intPart = intval($sale->total);
            $decPart = round(($sale->total - $intPart) * 100);
            $formatter = new \NumberFormatter('es_CO', \NumberFormatter::SPELLOUT);
            $words = mb_strtoupper($formatter->format($intPart));
        @endphp
        <div class="amount-words">
            Monto en letras: {{ $words }} CON {{ str_pad($decPart, 2, '0', STR_PAD_LEFT) }}/100
        </div>
        @endif

        <!-- DIAN Info -->
        @if($sale->is_electronic && $sale->cufe)
        <div class="dian-section">
            <div class="dian-title">✓ VALIDADA POR LA DIAN</div>
            <div>CUFE:</div>
            <div class="cufe">{{ $sale->cufe }}</div>
            @if($sale->qr_code)
            <div class="qr-container">
                <img src="{{ $sale->qr_code }}" alt="QR DIAN" onerror="this.style.display='none'">
            </div>
            @endif
        </div>
        @endif

        <!-- Footer -->
        @if($options['show_footer'])
        <div class="invoice-footer">
            <div class="footer-thanks">Gracias por su preferencia</div>
            @if($sale->branch->receipt_header)
                <div>{{ $sale->branch->receipt_header }}</div>
            @endif
            <div class="seller-info">
                {{ $sale->branch->name }} | {{ now()->format('d/m/Y H:i') }}
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
