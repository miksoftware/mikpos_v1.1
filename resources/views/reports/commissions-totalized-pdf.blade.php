<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Comisiones - Totalizado</title>
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
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-green { color: #10b981; }
        .text-purple { color: #a855f7; }
        .grand-total { background: #ecfdf5 !important; }
        .grand-total td { font-weight: bold; color: #10b981; font-size: 10px; }
        .badge-service { display: inline-block; background: #e0e7ff; color: #4338ca; font-size: 7px; padding: 1px 4px; border-radius: 3px; font-weight: bold; margin-right: 3px; }
        .badge-product { display: inline-block; background: #dbeafe; color: #1d4ed8; font-size: 7px; padding: 1px 4px; border-radius: 3px; font-weight: bold; margin-right: 3px; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>REPORTE DE COMISIONES - TOTALIZADO</h1>
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

        <div class="section-title">TOTALIZADO POR PRODUCTO / SERVICIO</div>

        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Producto / Servicio</th>
                    <th>SKU</th>
                    <th>Categoría</th>
                    <th class="text-center">Cant. Total</th>
                    <th class="text-right">Ventas</th>
                    <th class="text-right">Comisión</th>
                </tr>
            </thead>
            <tbody>
                @forelse($totalizedData as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($row['is_service'])
                            <span class="badge-service">SERV</span>
                        @else
                            <span class="badge-product">PROD</span>
                        @endif
                    </td>
                    <td>{{ Str::limit($row['product_name'], 28) }}</td>
                    <td>{{ $row['product_sku'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($row['quantity'], 3), '0'), '.') }}</td>
                    <td class="text-right">${{ number_format($row['total_sales'], 0, ',', '.') }}</td>
                    <td class="text-right text-green">${{ number_format($row['total_commission'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #94a3b8;">No hay datos de comisiones para los filtros seleccionados.</td>
                </tr>
                @endforelse
                @if(count($totalizedData) > 0)
                <tr class="grand-total">
                    <td colspan="5" class="text-right">TOTAL GENERAL:</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format(collect($totalizedData)->sum('quantity'), 3), '0'), '.') }}</td>
                    <td class="text-right">${{ number_format($totalSales, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($totalCommissions, 0, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        {{-- Resumen por vendedor --}}
        @if(count($userCommissions) > 0)
        <div class="section-title">RESUMEN POR VENDEDOR</div>

        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vendedor</th>
                    <th class="text-center">Items</th>
                    <th class="text-right">Ventas</th>
                    <th class="text-right">Comisión</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userCommissions as $index => $userData)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $userData['user_name'] }}</td>
                    <td class="text-center">{{ number_format($userData['items_count']) }}</td>
                    <td class="text-right">${{ number_format($userData['sales'], 0, ',', '.') }}</td>
                    <td class="text-right text-green">${{ number_format($userData['commission'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="grand-total">
                    <td colspan="3" class="text-right">TOTAL:</td>
                    <td class="text-right">${{ number_format($totalSales, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($totalCommissions, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        <div class="footer">
            <p>MikPOS - Sistema de Punto de Venta | Reporte generado automáticamente</p>
        </div>
    </div>
</body>
</html>
