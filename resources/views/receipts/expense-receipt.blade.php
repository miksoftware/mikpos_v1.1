<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=72mm">
    <title>Comprobante de Gasto {{ $expense->expense_number }}</title>
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
            font-size: 9px;
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

        /* Detail */
        .detail-box {
            background: #f9f9f9;
            padding: 6px;
            border: 1px dotted #ccc;
            margin-top: 4px;
            font-size: 11px;
            line-height: 1.35;
            word-wrap: break-word;
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
            @if($showLogo && $expense->branch?->logo)
            <div style="text-align:center;">
                <img src="{{ Storage::url($expense->branch->logo) }}" alt="{{ $expense->branch->name }}" class="branch-logo">
            </div>
            @endif
            <div class="business-name">{{ $expense->branch?->name ?? config('app.name') }}</div>
            <div class="business-info">
                @if($expense->branch?->tax_id)<p>NIT: {{ $expense->branch->tax_id }}</p>@endif
                @if($expense->branch?->address)<p>{{ $expense->branch->address }}</p>@endif
                @if($expense->branch?->municipality)<p>{{ $expense->branch->municipality->name }}@if($expense->branch->department), {{ $expense->branch->department->name }}@endif</p>@endif
                @if($expense->branch?->phone)<p>Tel: {{ $expense->branch->phone }}</p>@endif
            </div>
        </div>

        <!-- Document Info -->
        <div class="doc-info">
            <span class="doc-type">COMPROBANTE DE GASTO</span>
            <div class="doc-number">{{ $expense->expense_number }}</div>
            <div class="date-time">
                Fecha: {{ $expense->expense_date ? $expense->expense_date->format('d/m/Y') : $expense->created_at->format('d/m/Y') }}
                @if($expense->created_at) - {{ $expense->created_at->format('H:i') }}@endif
            </div>
        </div>

        <!-- Beneficiary / Contact (if any) -->
        @if($contact)
        <div class="section">
            <div class="section-title">BENEFICIARIO ({{ strtoupper($contact['type']) }}):</div>
            <div class="field-value">{{ $contact['name'] }}</div>
            @if(!empty($contact['document_number']))
            <div class="field-sub">{{ $contact['document_type'] }}: {{ $contact['document_number'] }}</div>
            @endif
            @if(!empty($contact['phone']))
            <div class="field-sub">Tel: {{ $contact['phone'] }}</div>
            @endif
        </div>
        @elseif($expense->contact_name)
        <div class="section">
            <div class="section-title">BENEFICIARIO:</div>
            <div class="field-value">{{ $expense->contact_name }}</div>
        </div>
        @endif

        <!-- Responsible / Cajero -->
        <div class="section">
            <div class="section-title">REGISTRADO POR:</div>
            <div class="field-value">{{ $expense->user?->name ?? 'Usuario' }}</div>
            @if($expense->branch)
            <div class="field-sub">Sucursal: {{ $expense->branch->name }}</div>
            @endif
        </div>

        <!-- Description / Concept -->
        <div class="section">
            <div class="section-title">CONCEPTO / DESCRIPCIÓN:</div>
            <div class="detail-box">
                {{ $expense->description }}
            </div>
        </div>

        <!-- Payment Method(s) -->
        <div class="section">
            <div class="section-title">FORMA(S) DE PAGO:</div>
            @if(!empty($expense->payment_details) && is_array($expense->payment_details) && count($expense->payment_details) > 0)
                @php
                    $methodIds = array_column($expense->payment_details, 'method_id');
                    $methods = \App\Models\PaymentMethod::whereIn('id', $methodIds)->get()->keyBy('id');
                @endphp
                @foreach($expense->payment_details as $p)
                    @php $pmName = $methods->get($p['method_id'])?->name ?? 'Forma de Pago'; @endphp
                    <div class="payment-row">
                        <span>{{ $pmName }}:</span>
                        <span>${{ number_format((float)($p['amount'] ?? 0), 2) }}</span>
                    </div>
                @endforeach
            @else
                <div class="payment-row">
                    <span>{{ $expense->paymentMethod?->name ?? 'Efectivo' }}:</span>
                    <span>${{ number_format((float)$expense->amount, 2) }}</span>
                </div>
            @endif
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span>TOTAL GASTO:</span>
                <span>${{ number_format((float)$expense->amount, 2) }}</span>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-line">
                Entrega (Responsable)<br>
                {{ Str::limit($expense->user?->name ?? '', 20) }}
            </div>
            <div class="signature-line">
                Recibe (Beneficiario)<br>
                C.C. / NIT
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Comprobante de Egreso - Soporte de Caja</p>
            <p style="font-size: 8px; margin-top: 2px;">Generado por Sistema POS</p>
        </div>
    </div>
</body>
</html>
