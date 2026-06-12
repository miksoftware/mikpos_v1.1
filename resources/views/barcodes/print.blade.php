@php
    $labelWidth = config('barcode.label_width_mm', 33);
    $labelHeight = config('barcode.label_height_mm', 22);
    $autoPrint = request()->boolean('print');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width={{ $labelWidth }}mm">
    <title></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            --label-width: {{ $labelWidth }}mm;
            --label-height: {{ $labelHeight }}mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: Arial, Helvetica, sans-serif;
            background: #e2e8f0;
            color: #000;
        }

        .screen-header {
            max-width: 720px;
            margin: 0 auto;
            padding: 24px 16px 0;
        }

        .screen-header h1 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .instructions {
            background: #fff7ed;
            border: 1px solid #fdba74;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.5;
            color: #9a3412;
        }

        .instructions strong {
            display: block;
            margin-bottom: 6px;
            color: #7c2d12;
        }

        .instructions ul {
            margin: 8px 0 0 18px;
        }

        .preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .preview-meta {
            font-size: 12px;
            color: #64748b;
        }

        .btn-print {
            background: #ff7261;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-print:hover {
            background: #e55a4a;
        }

        .labels-container {
            max-width: 720px;
            margin: 0 auto;
            padding: 0 16px 32px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .label-page {
            width: var(--label-width);
            height: var(--label-height);
            background: #fff;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1mm 1.5mm;
        }

        .price {
            font-size: 7pt;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5mm;
            white-space: nowrap;
        }

        .barcode-svg {
            display: block;
            width: calc(var(--label-width) - 3mm);
            max-width: 100%;
            height: auto;
            max-height: calc(var(--label-height) - 7mm);
        }

        @media print {
            @page {
                size: {{ $labelWidth }}mm {{ $labelHeight }}mm;
                margin: 0;
            }

            html, body {
                width: var(--label-width);
                height: auto;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .labels-container {
                display: block;
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .label-page {
                width: var(--label-width);
                height: var(--label-height);
                min-height: var(--label-height);
                max-height: var(--label-height);
                border: none;
                border-radius: 0;
                padding: 1mm 1.5mm;
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .label-page:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="screen-header no-print">
        <h1>Vista previa de etiquetas</h1>

        <div class="instructions">
            <strong>Antes de imprimir, configura el diálogo así:</strong>
            <ul>
                <li><strong>Márgenes:</strong> Ninguno / Sin márgenes</li>
                <li><strong>Escala:</strong> 100% (tamaño real, no “Ajustar a la página”)</li>
                <li><strong>Encabezados y pies de página:</strong> Desactivados</li>
                <li><strong>Tamaño de papel:</strong> {{ $labelWidth }} mm × {{ $labelHeight }} mm (o el rollo configurado en tu impresora)</li>
            </ul>
            Si ves fecha, URL o “1/3” en la impresión, los encabezados/pies siguen activos.
        </div>

        <div class="preview-toolbar">
            <div class="preview-meta">
                {{ collect($data)->sum('quantity') }} etiqueta(s) · Tamaño {{ $labelWidth }}×{{ $labelHeight }} mm
            </div>
            <button type="button" onclick="triggerPrint()" class="btn-print">Imprimir etiquetas</button>
        </div>
    </div>

    <div class="labels-container">
        @foreach($data as $item)
            @for($i = 0; $i < $item['quantity']; $i++)
                <div class="label-page">
                    <div class="price">${{ number_format($item['price'], 0, '', '') }}</div>
                    <svg class="barcode-svg"
                        jsbarcode-value="{{ $item['barcode'] }}"
                        jsbarcode-format="CODE128"
                        jsbarcode-width="1.2"
                        jsbarcode-height="28"
                        jsbarcode-displayValue="true"
                        jsbarcode-fontSize="8"
                        jsbarcode-fontOptions="bold"
                        jsbarcode-textMargin="0"
                        jsbarcode-margin="0">
                    </svg>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        function renderBarcodes() {
            JsBarcode('.barcode-svg', {
                format: 'CODE128',
                displayValue: true,
                fontOptions: 'bold',
                margin: 0,
                textMargin: 0,
            });
        }

        function triggerPrint() {
            renderBarcodes();
            setTimeout(function () {
                window.print();
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderBarcodes();

            @if($autoPrint)
            setTimeout(function () {
                window.print();
            }, 600);
            @endif
        });
    </script>
</body>
</html>
