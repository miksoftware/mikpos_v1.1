<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Catálogo de Productos - {{ $branch?->name ?? 'Tienda' }}</title>
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
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8px;
            line-height: 1.25;
            color: #1e293b;
            background: #ffffff;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #7c3aed;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo-col {
            width: 80px;
            padding-right: 8px;
        }
        .header-logo {
            max-width: 75px;
            max-height: 42px;
        }
        .logo-placeholder {
            width: 65px;
            height: 38px;
            background: #7c3aed;
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            line-height: 38px;
            border-radius: 4px;
            letter-spacing: 1px;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 1px;
            text-transform: uppercase;
        }
        .company-meta {
            font-size: 7px;
            color: #64748b;
            line-height: 1.2;
        }
        .company-meta strong {
            color: #334155;
        }
        .header-badge-col {
            width: 150px;
            text-align: right;
        }
        .catalog-badge-box {
            display: inline-block;
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 4px;
            padding: 4px 6px;
            text-align: right;
        }
        .catalog-title {
            font-size: 10px;
            font-weight: bold;
            color: #6d28d9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }
        .catalog-subtitle {
            font-size: 6.5px;
            color: #7c3aed;
            font-weight: bold;
        }
        .catalog-date {
            font-size: 6px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Stats Bar */
        .stats-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .stats-bar td {
            padding: 3px 6px;
            font-size: 7px;
            color: #475569;
        }
        .stats-bar td strong {
            color: #0f172a;
        }

        /* Category Banner */
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
            padding: 3px 6px;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .category-count {
            text-align: right;
            font-size: 6.5px;
            font-weight: normal;
        }

        /* Flat Product Items Table (Ultra fast for DomPDF) */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .product-row {
            border-bottom: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }
        .product-row td {
            padding: 4px 3px;
            vertical-align: top;
        }
        .img-cell {
            width: 48px;
            text-align: center;
        }
        .img-box {
            width: 44px;
            height: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #f8fafc;
            text-align: center;
            line-height: 42px;
        }
        .prod-img {
            max-width: 40px;
            max-height: 40px;
            vertical-align: middle;
        }
        .no-img-text {
            color: #cbd5e1;
            font-size: 12px;
        }
        .info-cell {
            text-align: left;
            padding-left: 6px;
            padding-right: 6px;
        }
        .prod-title {
            font-size: 8px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.15;
            margin-bottom: 1px;
        }
        .badge-sku {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            font-size: 5.5px;
            font-weight: bold;
            padding: 1px 2px;
            border-radius: 2px;
            margin-right: 2px;
        }
        .badge-brand {
            display: inline-block;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 5.5px;
            font-weight: bold;
            padding: 1px 2px;
            border-radius: 2px;
            margin-right: 2px;
        }
        .badge-unit {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 5.5px;
            font-weight: bold;
            padding: 1px 2px;
            border-radius: 2px;
        }
        .prod-desc {
            font-size: 6px;
            color: #64748b;
            line-height: 1.1;
            margin-top: 2px;
            margin-bottom: 2px;
            font-style: italic;
        }
        .variants-box {
            margin-top: 2px;
            font-size: 5.5px;
            color: #475569;
            background: #f8fafc;
            padding: 2px 3px;
            border-left: 2px solid #a855f7;
        }
        .variant-line {
            margin-bottom: 1px;
        }
        .price-cell {
            width: 95px;
            text-align: right;
            padding-right: 4px;
        }
        .main-price {
            font-size: 9.5px;
            font-weight: bold;
            color: #6d28d9;
            line-height: 1;
        }
        .sugg-price {
            font-size: 6.5px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-top: 1px;
        }
        .tax-indicator {
            font-size: 5.5px;
            color: #64748b;
            margin-top: 1px;
        }
        .stock-tag {
            display: inline-block;
            font-size: 5.5px;
            font-weight: bold;
            color: #059669;
            background: #ecfdf5;
            padding: 1px 3px;
            border-radius: 2px;
            margin-top: 2px;
        }
        .stock-qty {
            font-size: 5.5px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Footer */
        .catalog-footer {
            margin-top: 12px;
            padding-top: 5px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 6px;
            color: #94a3b8;
        }
        .footer-note {
            color: #64748b;
            margin-bottom: 1px;
        }
        .empty-catalog {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
            font-size: 9px;
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

            <!-- Products Flat Table -->
            <table class="products-table">
                @foreach($products as $prod)
                    <tr class="product-row">
                        <!-- Product Image -->
                        <td class="img-cell">
                            <div class="img-box">
                                @if(!empty($prod['image_base64']))
                                    <img src="{{ $prod['image_base64'] }}" alt="" class="prod-img">
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
                                    {{ \Illuminate\Support\Str::limit($prod['description'], 100) }}
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
                        </td>

                        <!-- Pricing & Stock -->
                        <td class="price-cell">
                            <div class="main-price">${{ number_format($prod['price_with_tax'], 0, ',', '.') }}</div>

                            @if(!empty($prod['suggested_price']) && $prod['suggested_price'] > $prod['price_with_tax'])
                                <div class="sugg-price">${{ number_format($prod['suggested_price'], 0, ',', '.') }}</div>
                            @endif

                            <div class="tax-indicator">({{ $prod['tax_label'] }})</div>

                            <div>
                                <span class="stock-tag">✓ Disponible</span>
                            </div>

                            @if($showStockInShop && $prod['manages_inventory'])
                                <div class="stock-qty">{{ rtrim(rtrim(number_format($prod['current_stock'], 3), '0'), '.') }} en stock</div>
                            @endif
                        </td>
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
                $font = $fontMetrics->get_font("Helvetica", "normal");
            } catch (\Throwable $e) {
                $font = null;
            }
            if ($font) {
                $size = 7;
                $color = array(0.4, 0.45, 0.5);
                $y = $pdf->get_height() - 18;
                $x = $pdf->get_width() - 80;
                $pdf->text($x, $y, $text, $font, $size, $color);
            }
        }
    </script>
</body>
</html>
