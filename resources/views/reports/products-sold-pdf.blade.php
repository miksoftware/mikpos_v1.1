<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Productos Vendidos</title>
    <style>
        @page {
            margin: 20px 30px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1e293b;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #a855f7;
        }
        .header h1 {
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 3px;
        }
        .header .subtitle {
            color: #64748b;
            font-size: 11px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            background: #f8fafc;
            border-radius: 5px;
        }
        .meta-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-table td:first-child {
            font-weight: bold;
            width: 120px;
            color: #64748b;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-box td {
            width: 50%;
            padding: 15px;
            text-align: center;
            vertical-align: top;
        }
        .summary-card {
            background: linear-gradient(135deg, #ff7261, #a855f7);
            color: white;
            padding: 15px;
            border-radius: 8px;
        }
        .summary-card.alt {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        }
        .summary-card .label {
            font-size: 9px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #a855f7;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table.data-table th {
            background: #a855f7;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        table.data-table td {
            padding: 6px;
            border-bottom: 1px solid #f1f5f9;
        }
        table.data-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            background: #f3e8ff;
            color: #7c3aed;
        }
        .rank {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-weight: bold;
            font-size: 9px;
        }
        .rank-1 { background: #fbbf24; color: #78350f; }
        .rank-2 { background: #cbd5e1; color: #334155; }
        .rank-3 { background: #d97706; color: white; }
        .rank-default { background: #e2e8f0; color: #64748b; }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 8px;
        }
        .top-products-grid {
            width: 100%;
        }
        .top-products-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 5px;
        }
        .top-product-item {
            background: #f8fafc;
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .top-product-item .name {
            font-weight: bold;
            color: #1e293b;
        }
        .top-product-item .sku {
            font-size: 8px;
            color: #64748b;
        }
        .top-product-item .stats {
            text-align: right;
            float: right;
        }
        .top-product-item .qty {
            font-weight: bold;
        }
        .top-product-item .revenue {
            font-size: 8px;
            color: #64748b;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Productos Vendidos</h1>
        <p class="subtitle">Análisis detallado de ventas por producto</p>
    </div>

    <table class="meta-table">
        <tr>
            <td>Período:</td>
            <td>{{ $startDate }} - {{ $endDate }}</td>
        </tr>
        <tr>
            <td>Sucursal:</td>
            <td>{{ $branchName }}</td>
        </tr>
        <tr>
            <td>Categoría:</td>
            <td>{{ $categoryName }}</td>
        </tr>
        <tr>
            <td>Generado:</td>
            <td>{{ $generatedAt }}</td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="label">UNIDADES VENDIDAS</div>
                    <div class="value">{{ number_format($totalQuantity, 0, ',', '.') }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card alt">
                    <div class="label">INGRESOS TOTALES</div>
                    <div class="value">${{ number_format($totalRevenue, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-title">Top 20 Productos Más Vendidos</h2>
        @if($topProducts->count() > 0)
        <table class="top-products-grid">
            <tr>
                <td>
                    @foreach($topProducts->take(10) as $index => $product)
                    <div class="top-product-item clearfix">
                        <span class="rank {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-default')) }}">{{ $index + 1 }}</span>
                        <span class="stats">
                            <span class="qty">{{ number_format($product->total_quantity) }} uds</span><br>
                            <span class="revenue">${{ number_format($product->total_revenue, 0, ',', '.') }}</span>
                        </span>
                        <span class="name">{{ Str::limit($product->product_name, 25) }}</span><br>
                        <span class="sku">{{ $product->product_sku }}</span>
                    </div>
                    @endforeach
                </td>
                <td>
                    @foreach($topProducts->skip(10)->take(10) as $index => $product)
                    <div class="top-product-item clearfix">
                        <span class="rank rank-default">{{ $index + 11 }}</span>
                        <span class="stats">
                            <span class="qty">{{ number_format($product->total_quantity) }} uds</span><br>
                            <span class="revenue">${{ number_format($product->total_revenue, 0, ',', '.') }}</span>
                        </span>
                        <span class="name">{{ Str::limit($product->product_name, 25) }}</span><br>
                        <span class="sku">{{ $product->product_sku }}</span>
                    </div>
                    @endforeach
                </td>
            </tr>
        </table>
        @else
        <div class="no-data">No hay productos vendidos en este período</div>
        @endif
    </div>

    <div class="section">
        <h2 class="section-title">Detalle de Ventas</h2>
        @if($items->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Factura</th>
                    <th>Producto</th>
                    <th>Cliente</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-right">P. Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items->take(80) as $item)
                <tr>
                    <td>{{ $item->sale->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $item->sale->invoice_number }}</td>
                    <td>
                        <strong>{{ Str::limit($item->product_name, 30) }}</strong><br>
                        <span style="color: #64748b; font-size: 8px;">{{ $item->product_sku }}</span>
                    </td>
                    <td>{{ Str::limit($item->sale->customer?->name ?? 'Consumidor Final', 20) }}</td>
                    <td class="text-center">
                        <span class="badge">{{ $item->quantity }}</span>
                    </td>
                    <td class="text-right">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right"><strong>${{ number_format($item->total, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($items->count() > 80)
        <p style="text-align: center; color: #64748b; margin-top: 10px; font-size: 9px;">
            Mostrando 80 de {{ $items->count() }} registros. Descargue el Excel para ver todos los datos.
        </p>
        @endif
        @else
        <div class="no-data">No hay ventas en este período</div>
        @endif
    </div>

    <div class="footer">
        <p>Este reporte fue generado automáticamente por MikPOS</p>
        <p>{{ $generatedAt }}</p>
    </div>
</body>
</html>
