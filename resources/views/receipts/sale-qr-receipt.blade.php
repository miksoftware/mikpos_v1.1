<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta QR - {{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: 50mm 50mm;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 100%;
            height: 100%;
            max-height: 48mm;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            overflow: hidden;
        }

        .label-container {
            width: 100%;
            height: 100%;
            max-height: 48mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 1.5mm 1mm;
            gap: 1.5mm;
        }

        .branch-name {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000000;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 95%;
        }

        .qr-image {
            width: 31mm;
            height: 31mm;
            object-fit: contain;
            image-rendering: pixelated;
        }

        .badge-scan {
            background-color: #000000 !important;
            color: #ffffff !important;
            padding: 2px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-align: center;
            line-height: 1.2;
        }

        @media print {
            @page {
                size: 50mm 50mm;
                margin: 0;
            }
            html, body {
                width: 100%;
                height: 100%;
                max-height: 48mm;
                margin: 0;
                padding: 0;
            }
            .label-container {
                max-height: 48mm;
            }
            .badge-scan {
                background-color: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="label-container">
        <div class="branch-name">
            {{ $sale->branch->name ?? 'MikPOS' }}
        </div>

        <img src="https://quickchart.io/qr?text={{ urlencode(url('/sale-public/' . $snapshot->qr_token)) }}&size=300" alt="QR Venta {{ $sale->invoice_number }}" class="qr-image">

        <div class="badge-scan">
            ¡Escanéeme!
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
