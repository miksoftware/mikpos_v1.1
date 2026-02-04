<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Comisiones</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #a855f7; }
        .header h1 { font-size: 18px; color: #1e293b; margin-bottom: 5px; }
        .header p { color: #64748b; font-size: 10px; }
        .meta-info { margin-bottom: 15px; }
        .meta-info table { width: 100%; }
        .meta-info td { padding: 3px 10px 3px 0; }
        .meta-info .label { font-weight: bold; color: #64748b; width: 100px; }
        .summary { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 50%; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: center; }
        .summary-card .value { font-size: 16px; font-weight: bold; color: #10b981; }
        .summary-card .label { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .section-title { font-size: 12px; font-weight: bold; color: #a855f7; margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 1px solid #e2e8f0; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data th { background: #a855f7; color: white; padding: 8px 5px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.data td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        table.data tr:nth-child(even) { background: #f8fafc; }
        .user-header { background: #f1f5f9 !important; }
        .user-header td { font-weight: bold; color: #1e293b; padding: 10px 5px; }
        .user-total { background: #ecfdf5 !important; }
        .user-total td { font-weight: bold; color: #10b981; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-green { color: #10b981; }
        .text-purple { color: #a855f7; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 8px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>REPORTE DE COMISIONES</h1>
            <p>Generado el {{ $generatedAt }}</p>
        </div>

        <div class="meta-info">
            <table>
                <tr>
                    <td class="label">Período:</td>
                    <td>{{ $startDate }} - {{ $endDate }}</td>
                    <td class="label">Sucursal:</td>
                    <td>{{ $branchName }}</td>
                </tr>
                <tr>
                    <td class="label">Vendedor:</td>
                    <td>{{ $userName }}</td>
                    <td class="label">Categoría:</td>
                    <td>{{ $categoryName }}</td>
                </tr>
                <tr>
                    <td class="label">Marca:</td>
                    <td>{{ $brandName }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </table>
        </div>

        <div class="summary">
            <div class="summary-card">
                <div class="value">${{ number_format($totalCommissions, 0, ',', '.') }}</div>
                <div class="label">Total Comisiones</div>
            </div>
            <div class="summary-card">
                <div class="value" style="color: #a855f7;">${{ number_format($totalSales, 0, ',', '.') }}</div>
                <div class="label">Total Ventas con Comisión</div>
            </div>
        </div>

        <div class="section-title">DETALLE POR VENDEDOR</div>

        @foreach($userCommissions as $userIndex => $userData)
        <table class="data">
            <thead>
                <tr class="user-header">
                    <td colspan="7">
                        {{ $userData['user_name'] }} 
                        <span style="float: right; color: #10b981;">
                            Comisión Total: ${{ number_format($userData['commission'], 0, ',', '.') }} | 
                            Ventas: ${{ number_format($userData['sales'], 0, ',', '.') }} |
                            Items: {{ number_format($userData['items_count']) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <th>Factura</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Comisión</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userData['items'] as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['invoice_number'] }}</td>
                    <td>{{ Str::limit($item['product_name'], 25) }}</td>
                    <td>{{ $item['category'] }}</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-right">${{ number_format($item['total'], 0, ',', '.') }}</td>
                    <td class="text-right text-green">${{ number_format($item['commission'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="user-total">
                    <td colspan="5" class="text-right">TOTAL {{ $userData['user_name'] }}:</td>
                    <td class="text-right">${{ number_format($userData['sales'], 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($userData['commission'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        
        @if(!$loop->last && count($userData['items']) > 15)
        <div class="page-break"></div>
        @endif
        @endforeach

        @if(count($userCommissions) === 0)
        <p style="text-align: center; padding: 30px; color: #94a3b8;">No hay datos de comisiones para los filtros seleccionados.</p>
        @endif

        <div class="footer">
            <p>MikPOS - Sistema de Punto de Venta | Reporte generado automáticamente</p>
        </div>
    </div>
</body>
</html>
