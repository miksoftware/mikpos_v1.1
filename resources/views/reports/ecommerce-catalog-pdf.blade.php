<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Catálogo de Productos - {{ $branch?->name ?? 'Tienda' }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 8mm 9mm 8mm 9mm;
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
            margin-bottom: 6px;
            padding-bottom: 5px;
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
            max-height: 40px;
        }
        .logo-placeholder {
            width: 65px;
            height: 36px;
            background: #7c3aed;
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            line-height: 36px;
            border-radius: 4px;
            letter-spacing: 1px;
        }
        .company-name {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 1px;
            text-transform: uppercase;
        }
        .company-meta {
            font-size: 6.5px;
            color: #64748b;
            line-height: 1.2;
        }
        .company-meta strong {
            color: #334155;
        }
        .header-badge-col {
            width: 140px;
            text-align: right;
        }
        .catalog-badge-box {
            display: inline-block;
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 4px;
            padding: 3px 6px;
            text-align: right;
        }
        .catalog-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #6d28d9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1px;
        }
        .catalog-subtitle {
            font-size: 6px;
            color: #7c3aed;
            font-weight: bold;
        }
        .catalog-date {
            font-size: 5.5px;
            color: #64748b;
            margin-top: 1px;
        }

        /* Category Banner */
        .category-header-wrap {
            margin-top: 6px;
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
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .category-count {
            text-align: right;
            font-size: 6.5px;
            font-weight: normal;
        }

        /* Grid Table (2 Columns x 4 Rows = 8 Products per Page) */
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
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            background: #ffffff;
            padding: 5px;
            height: 82px;
            box-sizing: border-box;
        }
        .card-inner-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-inner-table td {
            vertical-align: top;
        }

        /* Large Image Cell */
        .card-img-td {
            width: 78px;
            padding-right: 6px;
            text-align: center;
        }
        .card-img-box {
            width: 74px;
            height: 74px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #f8fafc;
            text-align: center;
            line-height: 72px;
            overflow: hidden;
        }
        .card-img {
            max-width: 70px;
            max-height: 70px;
            vertical-align: middle;
        }
        .no-img-text {
            color: #cbd5e1;
            font-size: 8px;
            font-weight: bold;
            line-height: 72px;
        }

        /* Info Cell */
        .card-info-td {
            text-align: left;
            vertical-align: top;
        }
        .card-title {
            font-size: 8px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.15;
            margin-bottom: 2px;
            max-height: 20px;
            overflow: hidden;
        }
        .card-badges {
            margin-bottom: 2px;
        }
        .badge-sku {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            font-size: 5.5px;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
            margin-right: 2px;
        }
        .badge-brand {
            display: inline-block;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 5.5px;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
        }
        .card-desc {
            font-size: 5.5px;
            color: #64748b;
            line-height: 1.1;
            margin-bottom: 2px;
            font-style: italic;
        }
        .card-variants {
            font-size: 5px;
            color: #475569;
            background: #f8fafc;
            padding: 1px 3px;
            border-left: 2px solid #a855f7;
            margin-bottom: 2px;
        }
        .card-price-box {
            margin-top: 3px;
            padding-top: 2px;
            border-top: 1px dashed #e2e8f0;
        }
        .card-main-price {
            font-size: 10px;
            font-weight: bold;
            color: #6d28d9;
            line-height: 1;
        }
        .card-sugg-price {
            font-size: 6.5px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-left: 2px;
        }
        .card-tax-label {
            font-size: 5.5px;
            color: #64748b;
            margin-left: 2px;
        }

        /* Footer */
        .catalog-footer {
            margin-top: 10px;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 5.5px;
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

            <!-- 8 Products per Page (2 Columns Grid) -->
            <table class="grid-table">
                @foreach(array_chunk($products, 2) as $row)
                    <tr class="grid-row">
                        @foreach($row as $prod)
                            <td class="grid-cell">
                                <div class="product-card">
                                    <table class="card-inner-table">
                                        <tr>
                                            <!-- Large Product Image -->
                                            <td class="card-img-td">
                                                <div class="card-img-box">
                                                    @if(!empty($prod['image_base64']))
                                                        <img src="{{ $prod['image_base64'] }}" alt="" class="card-img">
                                                    @else
                                                        <span class="no-img-text">SIN FOTO</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- Product Info -->
                                            <td class="card-info-td">
                                                <div class="card-title">{{ $prod['name'] }}</div>

                                                <div class="card-badges">
                                                    @if(!empty($prod['sku']))
                                                        <span class="badge-sku">{{ $prod['sku'] }}</span>
                                                    @endif
                                                    @if(!empty($prod['brand_name']))
                                                        <span class="badge-brand">{{ $prod['brand_name'] }}</span>
                                                    @endif
                                                </div>

                                                @if(!empty($prod['description']))
                                                    <div class="card-desc">
                                                        {{ \Illuminate\Support\Str::limit($prod['description'], 55) }}
                                                    </div>
                                                @endif

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

                                                <!-- Pricing -->
                                                <div class="card-price-box">
                                                    <span class="card-main-price">${{ number_format($prod['price_with_tax'], 0, ',', '.') }}</span>

                                                    @if(!empty($prod['suggested_price']) && $prod['suggested_price'] > $prod['price_with_tax'])
                                                        <span class="card-sugg-price">${{ number_format($prod['suggested_price'], 0, ',', '.') }}</span>
                                                    @endif

                                                    <span class="card-tax-label">({{ $prod['tax_label'] }})</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
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
