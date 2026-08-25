<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Catálogo de Productos - {{ $branch->name ?? 'Tienda' }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 10mm 12mm 12mm 12mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #1e293b;
            background: #ffffff;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #7c3aed;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo-col {
            width: 85px;
            padding-right: 10px;
        }
        .header-logo {
            max-width: 80px;
            max-height: 48px;
        }
        .logo-placeholder {
            width: 70px;
            height: 42px;
            background: #7c3aed;
            color: #ffffff;
            font-weight: bold;
            font-size: 13px;
            text-align: center;
            line-height: 42px;
            border-radius: 5px;
            letter-spacing: 1px;
        }
        .header-info-col {
            text-align: left;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .company-meta {
            font-size: 7.5px;
            color: #64748b;
            line-height: 1.25;
        }
        .company-meta strong {
            color: #334155;
        }
        .header-badge-col {
            width: 160px;
            text-align: right;
        }
        .catalog-badge-box {
            display: inline-block;
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 5px;
            padding: 5px 8px;
            text-align: right;
        }
        .catalog-title {
            font-size: 11px;
            font-weight: bold;
            color: #6d28d9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }
        .catalog-subtitle {
            font-size: 7px;
            color: #7c3aed;
            font-weight: bold;
        }
        .catalog-date {
            font-size: 6.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Stats Summary Strip */
        .stats-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .stats-bar td {
            padding: 4px 8px;
            font-size: 7.5px;
            color: #475569;
        }
        .stats-bar td strong {
            color: #0f172a;
        }

        /* Category Header */
        .category-header-wrap {
            margin-top: 8px;
            margin-bottom: 4px;
            page-break-after: avoid;
        }
        .category-banner-table {
            width: 100%;
            border-collapse: collapse;
            background: #6d28d9;
            border-radius: 3px;
        }
        .category-banner-table td {
            padding: 4px 8px;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .category-count {
            text-align: right;
            font-size: 7px;
            font-weight: normal;
        }

        /* Products Grid Table (2 Columns) */
        .products-grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .products-grid-table td.product-col {
            width: 50%;
            vertical-align: top;
            padding: 3px;
        }
        .product-card {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 5px;
            padding: 6px;
        }
        .card-inner-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-inner-table td {
            vertical-align: top;
        }
        .img-cell {
            width: 58px;
            padding-right: 6px;
            text-align: center;
        }
        .img-box {
            width: 54px;
            height: 54px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #f8fafc;
            text-align: center;
            line-height: 52px;
        }
        .prod-img {
            max-width: 50px;
            max-height: 50px;
            vertical-align: middle;
        }
        .no-img-text {
            color: #cbd5e1;
            font-size: 14px;
        }
        .info-cell {
            text-align: left;
        }
        .prod-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 2px;
        }
        .badge-sku {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            font-size: 6px;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
            margin-right: 2px;
        }
        .badge-brand {
            display: inline-block;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 6px;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
            margin-right: 2px;
        }
        .badge-unit {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 6px;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
        }
        .prod-desc {
            font-size: 6.5px;
            color: #64748b;
            line-height: 1.15;
            margin-top: 2px;
            margin-bottom: 3px;
            font-style: italic;
        }
        .price-row {
            margin-top: 3px;
            padding-top: 2px;
            border-top: 1px dashed #e2e8f0;
        }
        .main-price {
            font-size: 10px;
            font-weight: bold;
            color: #6d28d9;
        }
        .sugg-price {
            font-size: 7px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-left: 3px;
        }
        .tax-indicator {
            font-size: 6px;
            color: #64748b;
            margin-left: 2px;
        }
        .stock-tag {
            display: inline-block;
            font-size: 6px;
            font-weight: bold;
            color: #059669;
            background: #ecfdf5;
            padding: 1px 3px;
            border-radius: 2px;
            margin-top: 2px;
        }
        .stock-qty {
            font-size: 6px;
            color: #475569;
            margin-left: 2px;
        }
        .variants-box {
            margin-top: 2px;
            font-size: 6px;
            color: #475569;
            background: #f8fafc;
            padding: 2px 4px;
            border-left: 2px solid #a855f7;
        }
        .variant-line {
            margin-bottom: 1px;
        }

        /* Footer */
        .catalog-footer {
            margin-top: 15px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 6.5px;
            color: #94a3b8;
        }
        .footer-note {
            color: #64748b;
            margin-bottom: 1px;
        }

        .empty-catalog {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <!-- Company Logo -->
            <td class="header-logo-col">
                @if(!empty($branchLogoBase64))
                    <img src="{{ $branchLogoBase64 }}" alt="Logo" class="header-logo">
                @else
                    <div class="logo-placeholder">
                        {{ strtoupper(substr($branch?->name ?? 'POS', 0, 4)) }}
                    </div>
                @endif
            </td>

            <!-- Company Info -->
            <td class="header-info-col">
                <div class="company-name">{{ $branch?->name ?? 'Mi Empresa' }}</div>
                <div class="company-meta">
                    @if(!empty($branch?->tax_id))
                        <span><strong>NIT/RUT:</strong> {{ $branch->tax_id }}</span> · 
                    @endif
                    @if(!empty($branch?->phone))
                        <span><strong>Tel/WhatsApp:</strong> {{ $branch->phone }}</span>
                    @endif
                </div>
                <div class="company-meta">
                    @if(!empty($branch?->address))
                        <span><strong>Dirección:</strong> {{ $branch->address }}</span>
                    @endif
                    @if(!empty($branch?->city) || !empty($branch?->municipality))
                        <span> · {{ $branch?->municipality?->name ?? $branch?->city }}{{ !empty($branch?->department) ? ', ' . $branch->department->name : '' }}</span>
                    @endif
                </div>
                @if(!empty($branch?->email))
                    <div class="company-meta">
                        <span><strong>Email:</strong> {{ $branch->email }}</span>
                    </div>
                @endif
            </td>

            <!-- Catalog Badge -->
            <td class="header-badge-col">
                <div class="catalog-badge-box">
                    <div class="catalog-title">Catálogo Tienda</div>
                    <div class="catalog-subtitle">Productos Disponibles</div>
                    <div class="catalog-date">Emisión: {{ $generatedDate }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Quick Stats Bar -->
    <table class="stats-bar">
        <tr>
            <td style="width: 33%;">
                <strong>Categorías:</strong> {{ $totalCategories }}
            </td>
            <td style="width: 33%; text-align: center;">
                <strong>Total Productos:</strong> {{ $totalProducts }}
            </td>
            <td style="width: 34%; text-align: right;">
                <strong>Moneda:</strong> {{ $currencySymbol }}
            </td>
        </tr>
    </table>

    @if(count($categorizedProducts) > 0)
        @foreach($categorizedProducts as $categoryName => $products)
            <!-- Category Banner -->
            <div class="category-header-wrap">
                <table class="category-banner-table">
                    <tr>
                        <td>{{ $categoryName }}</td>
                        <td class="category-count">{{ count($products) }} {{ count($products) === 1 ? 'producto' : 'productos' }}</td>
                    </tr>
                </table>
            </div>

            <!-- Products 2-Column Grid -->
            <table class="products-grid-table">
                @foreach(array_chunk($products, 2) as $row)
                    <tr>
                        @foreach($row as $prod)
                            <td class="product-col">
                                <div class="product-card">
                                    <table class="card-inner-table">
                                        <tr>
                                            <!-- Product Image -->
                                            <td class="img-cell">
                                                <div class="img-box">
                                                    @if(!empty($prod['image_base64']))
                                                        <img src="{{ $prod['image_base64'] }}" alt="{{ $prod['name'] }}" class="prod-img">
                                                    @else
                                                        <span class="no-img-text">■</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Product Info -->
                                            <td class="info-cell">
                                                <div class="prod-title">{{ $prod['name'] }}</div>

                                                <div>
                                                    @if(!empty($prod['sku']))
                                                        <span class="badge-sku">{{ $prod['sku'] }}</span>
                                                    @endif
                                                    @if(!empty($prod['brand_name']))
                                                        <span class="badge-brand">{{ $prod['brand_name'] }}</span>
                                                    @endif
                                                    @if(!empty($prod['unit_name']))
                                                        <span class="badge-unit">{{ $prod['unit_name'] }}</span>
                                                    @endif
                                                </div>

                                                @if(!empty($prod['description']))
                                                    <div class="prod-desc">
                                                        {{ \Illuminate\Support\Str::limit($prod['description'], 65) }}
                                                    </div>
                                                @endif

                                                <!-- Variants (if any) -->
                                                @if(!empty($prod['variants']) && count($prod['variants']) > 0)
                                                    <div class="variants-box">
                                                        @foreach(array_slice($prod['variants'], 0, 3) as $var)
                                                            <div class="variant-line">
                                                                · {{ $var['name'] }}: <strong>${{ number_format($var['price'], 0, ',', '.') }}</strong>
                                                            </div>
                                                        @endforeach
                                                        @if(count($prod['variants']) > 3)
                                                            <div class="variant-line" style="color: #7c3aed; font-weight: bold;">
                                                                + {{ count($prod['variants']) - 3 }} variante(s) más
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                <!-- Pricing & Stock -->
                                                <div class="price-row">
                                                    <span class="main-price">${{ number_format($prod['price_with_tax'], 0, ',', '.') }}</span>

                                                    @if(!empty($prod['suggested_price']) && $prod['suggested_price'] > $prod['price_with_tax'])
                                                        <span class="sugg-price">${{ number_format($prod['suggested_price'], 0, ',', '.') }}</span>
                                                    @endif

                                                    <span class="tax-indicator">({{ $prod['tax_label'] }})</span>

                                                    <div>
                                                        <span class="stock-tag">✓ Disponible</span>
                                                        @if($showStockInShop && $prod['manages_inventory'])
                                                            <span class="stock-qty">({{ rtrim(rtrim(number_format($prod['current_stock'], 3), '0'), '.') }} en stock)</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        @endforeach

                        @if(count($row) === 1)
                            <td class="product-col"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @endforeach
    @else
        <div class="empty-catalog">
            <p>No hay productos disponibles actualmente en el catálogo de la tienda.</p>
        </div>
    @endif

    <!-- Footer Note -->
    <div class="catalog-footer">
        <p class="footer-note">Precios y disponibilidad sujetos a cambios sin previo aviso. Para pedidos y consultas, contáctanos a través de nuestra tienda virtual o WhatsApp.</p>
        <p>© {{ date('Y') }} {{ $branch?->name ?? 'MikPOS' }} · Todos los derechos reservados · Generado automáticamente</p>
    </div>

    <!-- Script for DomPDF page numbers -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            try {
                $font = $fontMetrics->get_font("DejaVu Sans", "normal") ?: $fontMetrics->get_font("Helvetica", "normal");
            } catch (\Throwable $e) {
                $font = $fontMetrics->get_font("Helvetica", "normal");
            }
            $size = 7.5;
            $color = array(0.4, 0.45, 0.5);
            $y = $pdf->get_height() - 20;
            $x = $pdf->get_width() - 85;
            $pdf->text($x, $y, $text, $font, $size, $color);
        }
    </script>
</body>
</html>
