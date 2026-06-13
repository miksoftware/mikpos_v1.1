<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Códigos de Barras</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            --paper-width: 104mm;
            --label-width: 32mm;
            --label-height: 18mm;
            --gap-x: 2mm;
            --gap-y: 2mm;
            --sheet-padding-x: 1mm;
            --sheet-padding-y: 1mm;
        }

        @page {
            margin: 0;
            size: var(--paper-width) auto;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: var(--paper-width);
            font-family: Arial, sans-serif;
            background: white;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .labels-container {
            width: var(--paper-width);
            display: grid;
            grid-template-columns: repeat(3, var(--label-width));
            grid-auto-rows: var(--label-height);
            column-gap: var(--gap-x);
            row-gap: var(--gap-y);
            padding: var(--sheet-padding-y) var(--sheet-padding-x);
            box-sizing: border-box;
            align-content: start;
        }

        .label {
            width: var(--label-width);
            height: var(--label-height);
            padding: 0.8mm 0.8mm 0.6mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            break-inside: avoid;
            page-break-inside: avoid;
            border: 0.1px solid #eee;
        }

        .price {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 0.4mm;
            line-height: 1;
        }

        .barcode-svg {
            display: block;
            width: 28.5mm;
            height: 6.8mm;
            max-width: 100%;
            overflow: hidden;
        }

        .sku {
            font-size: 5.5pt;
            font-weight: bold;
            margin-top: 0.4mm;
            line-height: 1;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        @media print {
            html,
            body {
                width: var(--paper-width);
            }

            .no-print {
                display: none !important;
            }

            .label {
                border: none;
            }
        }

        .controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            border: 1px solid #e2e8f0;
            z-index: 1000;
        }

        .btn-print {
            background: #ff7261;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <button onclick="window.print()" class="btn-print">Imprimir Ahora</button>
        <p style="font-size: 10px; margin-top: 10px; color: #64748b;">
            Configuración recomendada:<br>
            - Margen: Ninguno<br>
            - Escala: 100%<br>
            - Encabezado/Pie: Desactivado
        </p>
    </div>

    <div class="labels-container">
        @foreach($data as $item)
            @for($i = 0; $i < $item['quantity']; $i++)
                <div class="label">
                    <div class="price">${{ number_format($item['price'], 0, '', '') }}</div>
                    <svg class="barcode-svg"
                        jsbarcode-value="{{ $item['barcode'] }}"
                        jsbarcode-format="CODE128"
                        jsbarcode-width="1.35"
                        jsbarcode-height="24"
                        jsbarcode-fontSize="0"
                        jsbarcode-margin="0">
                    </svg>
                    <div class="sku">{{ $item['barcode'] }}</div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        JsBarcode(".barcode-svg").init();

        if (@json($autoPrint)) {
            window.onload = function () {
                setTimeout(function () {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>
