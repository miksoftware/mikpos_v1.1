@php
    $options = \App\Models\PrintFormatSetting::getLetterOptions('expense');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Egreso {{ $expense->expense_number }}</title>
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
            border-bottom: 2px solid #ea580c;
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
            background: #ea580c;
            color: #fff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .doc-number {
            font-size: 14px;
            font-weight: bold;
            color: #ea580c;
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

        /* Concept Table */
        .concept-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .concept-table thead th {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            padding: 7px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #9a3412;
        }

        .concept-table tbody td {
            border: 1px solid #e2e8f0;
            padding: 10px 10px;
            font-size: 11px;
            vertical-align: top;
            line-height: 1.4;
        }

        .concept-table tbody tr:nth-child(even) {
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
            width: 270px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
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
            color: #ea580c;
            border-top: 2px solid #ea580c;
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
            border-left: 3px solid #ea580c;
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
                @if($options['show_logo'] && $expense->branch?->logo)
                <div class="logo-box" style="margin-bottom: 6px;">
                    <img src="{{ Storage::url($expense->branch->logo) }}" alt="{{ $expense->branch->name }}">
                </div>
                @endif
                <div class="doc-main-title">COMPROBANTE DE EGRESO</div>
                <span class="doc-badge">GASTO DE OPERACIÓN</span>
            </div>
            <div class="doc-title-box">
                <div class="doc-number">{{ $expense->expense_number }}</div>
                <div class="doc-date">
                    Fecha: {{ $expense->expense_date ? $expense->expense_date->format('d/m/Y') : $expense->created_at->format('d/m/Y') }}
                    @if($expense->created_at) {{ $expense->created_at->format('H:i') }}@endif
                </div>
            </div>
        </div>

        <!-- 3-Column Info Grid -->
        <div class="info-grid">
            @if($options['show_business'])
            <div class="info-card">
                <div class="info-card-title">Empresa / Emisor</div>
                <div class="info-card-content">
                    <strong>{{ $expense->branch?->name ?? config('app.name') }}</strong><br>
                    @if($expense->branch?->tax_id)NIT: {{ $expense->branch->tax_id }}<br>@endif
                    @if($expense->branch?->address){{ $expense->branch->address }}<br>@endif
                    @if($expense->branch?->municipality)
                        {{ $expense->branch->municipality->name }}@if($expense->branch->department), {{ $expense->branch->department->name }}@endif<br>
                    @endif
                    @if($expense->branch?->phone)Tel: {{ $expense->branch->phone }}@endif
                </div>
            </div>
            @endif

            @if($options['show_customer'])
            <div class="info-card">
                <div class="info-card-title">Beneficiario / Contacto</div>
                <div class="info-card-content">
                    @if($contact)
                        <strong>{{ $contact['name'] }}</strong><br>
                        <span style="font-size: 9px; background: #e2e8f0; padding: 1px 4px; border-radius: 3px; text-transform: uppercase;">{{ $contact['type'] }}</span><br>
                        @if(!empty($contact['document_number']))
                            {{ $contact['document_type'] }}: {{ $contact['document_number'] }}<br>
                        @endif
                        @if(!empty($contact['phone']))Tel: {{ $contact['phone'] }}<br>@endif
                        @if(!empty($contact['address'])){{ $contact['address'] }}@if(!empty($contact['city'])), {{ $contact['city'] }}@endif<br>@endif
                        @if(!empty($contact['email'])){{ $contact['email'] }}@endif
                    @elseif($expense->contact_name)
                        <strong>{{ $expense->contact_name }}</strong>
                    @else
                        <strong>Gastos Generales / Varios</strong><br>
                        <span style="color: #64748b;">Desembolso de caja menor</span>
                    @endif
                </div>
            </div>
            @endif

            @if($options['show_sale_info'])
            <div class="info-card">
                <div class="info-card-title">Información del Gasto</div>
                <div class="info-card-content">
                    <strong>Comprobante:</strong> {{ $expense->expense_number }}<br>
                    <strong>Fecha de Registro:</strong> {{ $expense->expense_date ? $expense->expense_date->format('d/m/Y') : $expense->created_at->format('d/m/Y') }}<br>
                    <strong>Elaborado por:</strong> {{ $expense->user?->name ?? 'Usuario' }}<br>
                    <strong>Sucursal:</strong> {{ $expense->branch?->name ?? 'Principal' }}<br>
                    <strong>Estado:</strong> Pagado / Aplicado
                </div>
            </div>
            @endif
        </div>

        <!-- Concept / Detail Table -->
        <table class="concept-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">#</th>
                    <th style="text-align: left;">CONCEPTO / DESCRIPCIÓN DEL GASTO</th>
                    <th style="width: 140px; text-align: right;">VALOR</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; font-weight: bold; color: #64748b;">1</td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a; margin-bottom: 2px;">{{ $expense->description }}</div>
                        <div style="font-size: 10px; color: #64748b;">
                            Fecha de gasto: {{ $expense->expense_date ? $expense->expense_date->format('d/m/Y') : $expense->created_at->format('d/m/Y') }}
                            | Sucursal: {{ $expense->branch?->name ?? 'Principal' }}
                        </div>
                    </td>
                    <td style="text-align: right; font-weight: bold; font-size: 12px; color: #0f172a;">
                        ${{ number_format((float)$expense->amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Totals & Payments Row -->
        <div class="bottom-section">
            @if($options['show_payment_info'])
            <div class="payment-box">
                <div style="font-weight: bold; text-transform: uppercase; font-size: 9.5px; color: #475569; margin-bottom: 4px;">
                    Forma(s) de Pago
                </div>
                @if(!empty($expense->payment_details) && is_array($expense->payment_details) && count($expense->payment_details) > 0)
                    @php
                        $methodIds = array_column($expense->payment_details, 'method_id');
                        $methods = \App\Models\PaymentMethod::whereIn('id', $methodIds)->get()->keyBy('id');
                    @endphp
                    @foreach($expense->payment_details as $p)
                        @php $pmName = $methods->get($p['method_id'])?->name ?? 'Forma de Pago'; @endphp
                        <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                            <span>• {{ $pmName }}:</span>
                            <span style="font-weight: 600;">${{ number_format((float)($p['amount'] ?? 0), 2) }}</span>
                        </div>
                    @endforeach
                @else
                    <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                        <span>• {{ $expense->paymentMethod?->name ?? 'Efectivo' }}:</span>
                        <span style="font-weight: 600;">${{ number_format((float)$expense->amount, 2) }}</span>
                    </div>
                @endif
            </div>
            @else
            <div></div>
            @endif

            <div class="totals-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>${{ number_format((float)$expense->amount, 2) }}</span>
                </div>
                <div class="total-row grand-total">
                    <span>TOTAL GASTO:</span>
                    <span>${{ number_format((float)$expense->amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Amount in Words -->
        @if($options['show_amount_words'])
        @php
            $intPart = intval($expense->amount);
            $decPart = round(($expense->amount - $intPart) * 100);
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
        @if($options['show_observations'] && !empty($expense->description))
        <div class="observations-box">
            <strong>Observaciones:</strong> Comprobante soporte de egreso por concepto de {{ $expense->description }}.
        </div>
        @endif

        <!-- Signatures -->
        <div class="signatures-row">
            <div class="signature-item">
                Elaborado por (Responsable)
                <div class="signature-sub">{{ $expense->user?->name ?? 'Firma Autorizada' }}</div>
            </div>
            <div class="signature-item">
                Recibí Conforme (Beneficiario)
                <div class="signature-sub">
                    @if($contact)
                        {{ $contact['name'] }}<br>
                        {{ $contact['document_type'] }}: {{ $contact['document_number'] ?? '____________________' }}
                    @else
                        Firma y C.C. / NIT
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        @if($options['show_footer'])
        <div class="document-footer">
            <p>Este documento es un comprobante interno de egreso y soporte contable de egresos de caja.</p>
            <p style="font-size: 8.5px; margin-top: 3px;">Sistema POS - {{ config('app.name') }}</p>
        </div>
        @endif
    </div>
</body>
</html>
