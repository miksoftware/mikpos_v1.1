<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Códigos de Barras</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @page {
            margin: 0;
            size: auto;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: white;
        }

        .labels-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            align-content: flex-start;
        }

        .label {
            width: 32mm;
            height: 18mm;
            padding: 1mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            page-break-inside: avoid;
            border: 0.1px solid #eee;
        }

        .price {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 0;
            line-height: 1;
        }

        .barcode-svg {
            width: 100%;
            height: auto;
            max-height: 8mm;
        }

        .sku {
            font-size: 7pt;
            font-weight: bold;
            margin-top: 1px;
            letter-spacing: 1px;
        }

        @media print {
            .no-print {
                display: none;
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
                        jsbarcode-width="1"
                        jsbarcode-height="30"
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
