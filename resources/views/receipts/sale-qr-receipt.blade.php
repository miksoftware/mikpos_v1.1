<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta QR - {{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: auto;
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
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ffffff;
            color: #000000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            overflow: hidden;
        }

        .qr-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100vw;
            height: 100vh;
            padding: 0;
            margin: 0;
        }

        .qr-image {
            width: 95vw;
            height: 95vh;
            max-width: 95mm;
            max-height: 95mm;
            object-fit: contain;
            image-rendering: pixelated;
        }

        @media print {
            @page {
                margin: 0;
            }
            html, body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
            }
            .qr-wrapper {
                width: 100vw;
                height: 100vh;
            }
            .qr-image {
                width: 95vw;
                height: 95vh;
            }
        }
    </style>
</head>
<body>

    <div class="qr-wrapper">
        {{-- The QR encodes the permanent public URL for this sale snapshot --}}
        <img src="https://quickchart.io/qr?text={{ urlencode(url('/sale-public/' . $snapshot->qr_token)) }}&size=500" alt="QR Venta {{ $sale->invoice_number }}" class="qr-image">
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
