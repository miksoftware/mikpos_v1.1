<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Pedidos Tienda</title>
    <style>
        @page {
            margin: 15px 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #1e293b;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #a855f7;
        }
        .header h1 { font-size: 16px; color: #1e293b; margin-bottom: 2px; }
        .header .subtitle { color: #64748b; font-size: 10px; }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            background: #f8fafc;
        }
        .meta-table td {
            padding: 5px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-table td:first-child {
            font-weight: bold;
            width: 100px;
            color: #64748b;
        }
        .summary-box { width: 100%; margin-bottom: 15px; }
        .summary-box td { width: 33%; padding: 8px; text-align: center; }
        .summary-card {
            background: linear-gradient(135deg, #ff7261, #a855f7);
            color: white;
            padding: 10px;
            border-radius: 6px;
        }
        .summary-card.alt { background: linear-gradient(135deg, #3b82f6, #8b5cf6); }
        .summary-card.green { background: linear-gradient(135deg, #10b981, #059669); }
        .summary-card .label { font-size: 8px; opacity: 0.9; margin-bottom: 3px; }
        .summary-card .value { font-size: 16px; font-weight: bold; }
</style>
</head>
<body>
    <div class="header">
        <h1>Tabla de Pedidos - Tienda</h1>
        <p class="subtitle">Productos por cliente y cantidades</p>
    </div>

    <table class="meta-table">
        <tr><td>Período:</td><td>{{ $startDate }} - {{ $endDate }}</td></tr>
        <tr><td>Estado:</td><td>{{ $statusLabel }}</td></tr>
        <tr><td>Generado:</td><td>{{ $generatedAt }}</td></tr>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="label">PRODUCTOS</div>
                    <div class="value">{{ count($products) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card alt">
                    <div class="label">CLIENTES</div>
                    <div class="value">{{ count($customers) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card green">
                    <div class="label">TOTAL UNIDADES</div>
                    <div class="value">{{ rtrim(rtrim(number_format($grandTotal, 3, ',', '.'), '0'), ',') }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if(count($products) > 0)
    <div style="margin-bottom: 10px;">
        <h2 style="font-size: 11px; font-weight: bold; color: #a855f7; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0;">
            Detalle por Producto y Cliente
        </h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
            <thead>
                <tr>
                    <th style="background: #a855f7; color: white; padding: 6px 4px; text-align: left; font-size: 7px; text-transform: uppercase; border: 1px solid #9333ea;">
                        Producto
                    </th>
                    @foreach($customers as $key => $name)
                    <th style="background: #a855f7; color: white; padding: 6px 3px; text-align: center; font-size: 7px; border: 1px solid #9333ea; max-width: 60px; word-wrap: break-word;">
                        {{ \Illuminate\Support\Str::limit($name, 15) }}
                    </th>
                    @endforeach
                    <th style="background: #7c3aed; color: white; padding: 6px 4px; text-align: center; font-size: 7px; text-transform: uppercase; border: 1px solid #6d28d9; font-weight: bold;">
                        TOTAL
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                <tr>
                    <td style="padding: 5px 4px; border: 1px solid #e2e8f0; {{ $loop->even ? 'background: #f8fafc;' : '' }}">
                        <strong>{{ $product['name'] }}</strong>
                        @if($product['sku'])
                        <br><span style="color: #64748b; font-size: 7px;">{{ $product['sku'] }}</span>
                        @endif
                    </td>
                    @foreach($customers as $custKey => $custName)
                    <td style="padding: 5px 3px; text-align: center; border: 1px solid #e2e8f0; {{ $loop->parent->even ? 'background: #f8fafc;' : '' }}">
                        @if(($product['quantities'][$custKey] ?? 0) > 0)
                            <strong>{{ rtrim(rtrim(number_format($product['quantities'][$custKey], 3), '0'), '.') }}</strong>
                        @endif
                    </td>
                    @endforeach
                    <td style="padding: 5px 4px; text-align: center; border: 1px solid #e2e8f0; background: #f3e8ff; font-weight: bold; color: #7c3aed;">
                        {{ rtrim(rtrim(number_format($product['total'], 3), '0'), '.') }}
                    </td>
                </tr>
                @endforeach
                {{-- Totals row --}}
                <tr>
                    <td style="padding: 6px 4px; border: 1px solid #9333ea; background: #7c3aed; color: white; font-weight: bold;">
                        TOTAL
                    </td>
                    @foreach($customers as $custKey => $custName)
                    <td style="padding: 6px 3px; text-align: center; border: 1px solid #9333ea; background: #7c3aed; color: white; font-weight: bold;">
                        @if(($customerTotals[$custKey] ?? 0) > 0)
                            {{ rtrim(rtrim(number_format($customerTotals[$custKey], 3), '0'), '.') }}
                        @endif
                    </td>
                    @endforeach
                    <td style="padding: 6px 4px; text-align: center; border: 1px solid #6d28d9; background: #581c87; color: white; font-weight: bold;">
                        {{ rtrim(rtrim(number_format($grandTotal, 3, '.', ','), '0'), '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align: center; padding: 40px; color: #94a3b8;">
        No hay datos para el período y filtros seleccionados.
    </div>
    @endif

    <div style="margin-top: 15px; padding-top: 8px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 7px;">
        <p>Reporte generado automáticamente por MikPOS</p>
        <p>{{ $generatedAt }}</p>
    </div>
</body>
</html>
