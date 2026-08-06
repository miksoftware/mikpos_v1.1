<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta QR - {{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: 100mm 100mm;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            width: 100mm;
            height: 100mm;
            padding: 4mm 6mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            background-color: #ffffff;
            color: #000000;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 500;
            color: #333333;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 3px;
        }

        .header-title {
            font-weight: 700;
            font-size: 12px;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 2mm 0;
        }

        .qr-image {
            width: 58mm;
            height: 58mm;
            object-fit: contain;
            image-rendering: pixelated;
        }

        .badge-scan {
            background-color: #000000 !important;
            color: #ffffff !important;
            padding: 6px 24px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-top: 2mm;
            width: 85%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .footer {
            font-size: 11px;
            font-weight: 600;
            color: #111111;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px dashed #ccc;
            padding-top: 3px;
        }

        @media print {
            body {
                width: 100mm;
                height: 100mm;
            }
            .badge-scan {
                background-color: #000000 !important;
                color: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <span>{{ $sale->created_at->format('d/m/Y, H:i') }}</span>
        <span class="header-title">{{ $sale->branch->name ?? 'MikPOS' }}</span>
    </div>

    <div class="qr-wrapper">
        {{-- The QR encodes the permanent public URL for this sale snapshot --}}
        <img src="https://quickchart.io/qr?text={{ urlencode(url('/sale-public/' . $snapshot->qr_token)) }}&size=300" alt="QR Venta {{ $sale->invoice_number }}" class="qr-image">
        
        <div class="badge-scan">
            ¡Escanéeme!
        </div>
    </div>

    <div class="footer">
        <span>{{ $sale->invoice_number }}</span>
        <span>Total: ${{ number_format($sale->total, 0, ',', '.') }}</span>
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
