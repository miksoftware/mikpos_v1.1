<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Catálogo de Productos - {{ $branch?->name ?? 'Tienda' }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 6mm 7mm 6mm 7mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #1e293b;
            background: #ffffff;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            padding-bottom: 4px;
            border-bottom: 2px solid #7c3aed;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo-col {
            width: 85px;
            padding-right: 8px;
        }
        .header-logo {
            max-width: 80px;
            max-height: 42px;
        }
        .logo-placeholder {
            width: 70px;
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
            padding: 4px 7px;
            text-align: right;
        }
        .catalog-title {
            font-size: 10.5px;
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
            font-size: 6px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Category Banner */
        .category-header-wrap {
            margin-top: 5px;
            margin-bottom: 3px;
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

        /* Grid Table (2 Columns x 3 Rows = 6 Products per Page) */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .grid-row {
            page-break-inside: avoid;
        }
        .grid-cell {
            width: 50%;
            vertical-align: top;
            padding: 3px;
        }

        /* Product Box / Card */
        .product-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            height: 80mm;
            box-sizing: border-box;
            overflow: hidden;
            text-align: center;
        }

        /* Extra Large Image Container */
        .card-img-container {
            width: 100%;
            height: 158px;
            line-height: 158px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            text-align: center;
            overflow: hidden;
        }
        .card-img {
            max-width: 96%;
            max-height: 154px;
            vertical-align: middle;
            display: inline-block;
        }
        .no-img-box {
            color: #cbd5e1;
            font-size: 10px;
            font-weight: bold;
            line-height: 158px;
        }

        /* Card Content Below Image */
        .card-body {
            padding: 5px 8px;
            text-align: left;
        }
        .card-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
            height: 22px;
            overflow: hidden;
            margin-bottom: 3px;
        }
        .card-badges {
            margin-bottom: 3px;
        }
        .badge-sku {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            font-size: 6.5px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
            margin-right: 3px;
        }
        .badge-brand {
            display: inline-block;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 6.5px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
        }
        .card-variants {
            font-size: 6.5px;
            color: #475569;
            background: #f8fafc;
            padding: 2px 4px;
            border-left: 2px solid #a855f7;
            margin-bottom: 3px;
        }
        .card-price-row {
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px dashed #e2e8f0;
        }
        .card-main-price {
            font-size: 14px;
            font-weight: bold;
            color: #6d28d9;
            line-height: 1;
        }
        .card-sugg-price {
            font-size: 8.5px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-left: 4px;
        }
        .card-tax-label {
            font-size: 7px;
            color: #64748b;
            margin-left: 3px;
        }

        /* Footer */
        .catalog-footer {
            margin-top: 8px;
            padding-top: 4px;
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

            <!-- 6 Products per Page (2 Columns x 3 Rows Grid) -->
            <table class="grid-table">
                @foreach(array_chunk($products, 2) as $row)
                    <tr class="grid-row">
                        @foreach($row as $prod)
                            <td class="grid-cell">
                                <div class="product-card">
                                    <!-- Extra Large Centered Product Image -->
                                    <div class="card-img-container">
                                        @if(!empty($prod['image_base64']))
                                            <img src="{{ $prod['image_base64'] }}" alt="" class="card-img">
                                        @else
                                            <div class="no-img-box">SIN FOTO</div>
                                        @endif
                                    </div>

                                    <!-- Product Info & Large Price -->
                                    <div class="card-body">
                                        <div class="card-title">{{ $prod['name'] }}</div>

                                        <div class="card-badges">
                                            @if(!empty($prod['sku']))
                                                <span class="badge-sku">{{ $prod['sku'] }}</span>
                                            @endif
                                            @if(!empty($prod['brand_name']))
                                                <span class="badge-brand">{{ $prod['brand_name'] }}</span>
                                            @endif
                                        </div>

                                        <!-- Variants (if any) -->
                                        @if(!empty($prod['variants']) && count($prod['variants']) > 0)
                                            <div class="card-variants">
                                                @foreach(array_slice($prod['variants'], 0, 2) as $var)
                                                    <div>· {{ $var['name'] }}: <strong>${{ number_format($var['price'], 0, ',', '.') }}</strong></div>
                                                @endforeach
                                                @if(count($prod['variants']) > 2)
                                                    <div style="color: #7c3aed; font-weight: bold;">+ {{ count($prod['variants']) - 2 }} más</div>
                                                @endif
                                            </div>
                                        @endif

                                        <!-- Large Pricing -->
                                        <div class="card-price-row">
                                            <span class="card-main-price">${{ number_format($prod['price_with_tax'], 0, ',', '.') }}</span>

                                            @if(!empty($prod['suggested_price']) && $prod['suggested_price'] > $prod['price_with_tax'])
                                                <span class="card-sugg-price">${{ number_format($prod['suggested_price'], 0, ',', '.') }}</span>
                                            @endif

                                            <span class="card-tax-label">({{ $prod['tax_label'] }})</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        @endforeach

                        @if(count($row) === 1)
                            <td class="grid-cell"></td>
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
                $font = $fontMetrics->get_font("Helvetica", "normal");
            } catch (\Throwable $e) {
                $font = null;
            }
            if ($font) {
                $size = 6.5;
                $color = array(0.4, 0.45, 0.5);
                $y = $pdf->get_height() - 16;
                $x = $pdf->get_width() - 75;
                $pdf->text($x, $y, $text, $font, $size, $color);
            }
        }
    </script>
</body>
</html>
