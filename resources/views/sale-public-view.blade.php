<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $snapshot->sale_snapshot['invoice_number'] ?? '' }} | {{ $snapshot->sale_snapshot['branch']['name'] ?? 'MikPOS' }}</title>
    <meta name="description" content="Consulta tu factura de compra y documentos de importación.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- PDF.js for cross-device inline rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:  #6366f1;
            --primary-light: #818cf8;
            --success:  #10b981;
            --warning:  #f59e0b;
            --danger:   #ef4444;
            --gray-50:  #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            color: var(--gray-900);
            padding: 24px 16px 48px;
        }

        .container { max-width: 720px; margin: 0 auto; }

        .brand-header {
            text-align: center;
            margin-bottom: 32px;
            padding: 24px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            backdrop-filter: blur(12px);
        }
        .branch-name { font-size: 26px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; margin-bottom: 4px; }
        .branch-info { font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.6; }

        .card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            margin-bottom: 20px;
        }

        .card-header {
            padding: 20px 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .card-header-title { font-size: 18px; font-weight: 700; }
        .card-header-subtitle { font-size: 12px; opacity: 0.8; }

        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 12px; border-radius: 999px;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-white     { background: rgba(255,255,255,0.2); color: white; }
        .badge-success   { background: #d1fae5; color: #065f46; }
        .badge-warning   { background: #fef3c7; color: #92400e; }
        .badge-electronic{ background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid rgba(52,211,153,0.3); }

        .card-body { padding: 24px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        @media (max-width: 480px) { .info-grid { grid-template-columns: 1fr; } }

        .info-item label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--gray-500); margin-bottom: 4px; }
        .info-item p     { font-size: 14px; font-weight: 600; color: var(--gray-900); }

        .divider { height: 1px; background: var(--gray-100); margin: 20px 0; }

        .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--gray-500); margin-bottom: 12px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th {
            text-align: left; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
            color: var(--gray-500); padding: 8px 12px;
            border-bottom: 2px solid var(--gray-100);
        }
        .items-table th:last-child { text-align: right; }
        .items-table td { padding: 12px; border-bottom: 1px solid var(--gray-100); font-size: 14px; vertical-align: top; }
        .items-table td:last-child { text-align: right; font-weight: 700; white-space: nowrap; }
        .items-table tr:last-child td { border-bottom: none; }
        .item-name { font-weight: 600; color: var(--gray-900); }
        .item-meta { font-size: 11px; color: var(--gray-500); margin-top: 2px; }
        .import-badge {
            display: inline-flex; align-items: center; gap: 3px;
            margin-top: 4px; padding: 2px 8px;
            background: #ede9fe; color: #6d28d9;
            border-radius: 999px; font-size: 10px; font-weight: 700;
        }

        .totals { background: var(--gray-50); border-radius: 12px; padding: 16px 20px; }
        .total-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 14px; color: var(--gray-700); }
        .total-row.grand { padding-top: 12px; margin-top: 8px; border-top: 2px solid var(--gray-200); font-size: 18px; font-weight: 800; color: var(--gray-900); }

        .payment-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background: var(--gray-50); border-radius: 10px; margin-bottom: 8px; font-size: 14px; font-weight: 600; }

        .dian-box { background: linear-gradient(135deg, #d1fae5, #a7f3d0); border: 1px solid #6ee7b7; border-radius: 12px; padding: 16px 20px; }
        .dian-box .dian-title { font-size: 13px; font-weight: 800; color: #065f46; margin-bottom: 6px; }
        .dian-box .cufe-text  { font-family: 'Courier New', monospace; font-size: 10px; word-break: break-all; color: #047857; line-height: 1.5; }

        .import-card { background: #ffffff; border: 2px solid #ddd6fe; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-bottom: 20px; }
        .import-card-header {
            background: linear-gradient(135deg, #7c3aed, #6366f1);
            color: white; padding: 16px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
        }
        .import-card-header .icon { font-size: 24px; }
        .import-card-header .info h3 { font-size: 15px; font-weight: 700; }
        .import-card-header .info p  { font-size: 11px; opacity: 0.8; margin-top: 2px; }
        .import-code-tag { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; padding: 4px 12px; font-size: 12px; font-weight: 700; font-family: 'Courier New', monospace; white-space: nowrap; }
        .import-card-body { padding: 20px; }
        .products-list { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
        .product-tag { background: #ede9fe; color: #5b21b6; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 600; }

        .pdf-link {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 14px 20px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; border-radius: 12px; font-size: 15px; font-weight: 700;
            text-decoration: none; transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(99,102,241,0.35);
        }
        .pdf-link:hover { background: linear-gradient(135deg, #4f46e5, #7c3aed); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(99,102,241,0.45); }
        .pdf-icon { width: 22px; height: 22px; background: rgba(255,255,255,0.25); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }

        /* Inline PDF viewer */
        .pdf-toggle-btn {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 14px 20px; cursor: pointer; border: none;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white; border-radius: 12px; font-size: 15px; font-weight: 700;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(99,102,241,0.35);
        }
        .pdf-toggle-btn:hover { background: linear-gradient(135deg, #4f46e5, #7c3aed); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(99,102,241,0.45); }

        .pdf-viewer-wrap {
            margin-top: 12px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #ddd6fe;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .pdf-viewer-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px;
            background: #f5f3ff;
            border-bottom: 1px solid #ddd6fe;
            color: #5b21b6; font-weight: 600;
        }

        .pdf-toolbar-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 8px;
            background: #7c3aed; color: white;
            font-size: 12px; font-weight: 700;
            text-decoration: none; transition: background .2s;
        }
        .pdf-toolbar-btn:hover { background: #6d28d9; }

        .no-imports { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 20px; text-align: center; color: rgba(255,255,255,0.5); font-size: 13px; margin-bottom: 20px; }
        .page-footer { text-align: center; color: rgba(255,255,255,0.3); font-size: 11px; margin-top: 24px; }
    </style>
</head>
<body>
@php
    $snap     = $snapshot->sale_snapshot;
    $branch   = $snap['branch']   ?? [];
    $customer = $snap['customer'] ?? null;
    $items    = $snap['items']    ?? [];
    $payments = $snap['payments'] ?? [];
    $imports  = $snapshot->import_declarations ?? [];

    $createdAt           = !empty($snap['created_at']) ? \Carbon\Carbon::parse($snap['created_at']) : null;
    $total               = (float) ($snap['total']               ?? 0);
    $subtotal            = (float) ($snap['subtotal']            ?? 0);
    $taxTotal            = (float) ($snap['tax_total']           ?? 0);
    $discount            = (float) ($snap['discount']            ?? 0);
    $globalDiscountAmount= (float) ($snap['global_discount_amount'] ?? 0);
    $globalDiscountValue = (float) ($snap['global_discount_value']  ?? 0);
    $globalDiscountType  = $snap['global_discount_type'] ?? '';
    $itemDiscount        = $discount - $globalDiscountAmount;
@endphp

<div class="container">

    {{-- Brand header --}}
    <div class="brand-header">
        <div class="branch-name">{{ $branch['name'] ?? 'MikPOS' }}</div>
        <div class="branch-info">
            @if(!empty($branch['tax_id']))
                NIT: {{ $branch['tax_id'] }}<br>
            @endif
            @if(!empty($branch['address']))
                {{ $branch['address'] }}<br>
            @endif
            @if(!empty($branch['municipality']) || !empty($branch['department']))
                {{ $branch['municipality'] ?? '' }}
                @if(!empty($branch['municipality']) && !empty($branch['department'])), @endif
                {{ $branch['department'] ?? '' }}<br>
            @endif
            @if(!empty($branch['phone']))
                Tel: {{ $branch['phone'] }}
            @endif
        </div>
    </div>

    {{-- Invoice card --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-header-title">
                    @if(!empty($snap['is_electronic']) && !empty($snap['cufe']))
                        <span class="badge badge-electronic">✓ FE</span>&nbsp;{{ $snap['dian_number'] ?? $snap['invoice_number'] }}
                    @else
                        {{ $snap['invoice_number'] }}
                    @endif
                </div>
                @if($createdAt)
                <div class="card-header-subtitle">{{ $createdAt->format('d/m/Y H:i:s') }}</div>
                @endif
            </div>
            <div>
                @php
                    $statusMap = [
                        'completed'        => ['Completada', 'badge-success'],
                        'pending_approval' => ['Pendiente',  'badge-warning'],
                    ];
                    $statusInfo = $statusMap[$snap['status'] ?? ''] ?? ['—', 'badge-white'];
                @endphp
                <span class="badge {{ $statusInfo[1] }}">{{ $statusInfo[0] }}</span>
            </div>
        </div>

        <div class="card-body">

            {{-- Customer --}}
            <div class="section-title">Cliente</div>
            @if($customer)
            <div class="info-grid">
                <div class="info-item">
                    <label>Nombre</label>
                    <p>{{ $customer['full_name'] }}</p>
                </div>
                @if(!empty($customer['document_number']))
                <div class="info-item">
                    <label>{{ $customer['tax_document_abbreviation'] ?? 'Documento' }}</label>
                    <p>{{ $customer['document_number'] }}</p>
                </div>
                @endif
                @if(!empty($customer['municipality']) || !empty($customer['department']))
                <div class="info-item">
                    <label>Ciudad</label>
                    <p>
                        {{ $customer['municipality'] ?? '' }}
                        @if(!empty($customer['municipality']) && !empty($customer['department'])), @endif
                        {{ $customer['department'] ?? '' }}
                    </p>
                </div>
                @endif
                @if(!empty($customer['address']))
                <div class="info-item">
                    <label>Dirección</label>
                    <p>{{ $customer['address'] }}</p>
                </div>
                @endif
            </div>
            @else
            <p style="color:var(--gray-500); font-size:14px; margin-bottom:16px;">Consumidor Final</p>
            @endif

            <div class="divider"></div>

            {{-- Items --}}
            <div class="section-title">Productos</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align:right">Cant</th>
                        <th style="text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    @php
                        $qty        = rtrim(rtrim(number_format((float)($item['quantity'] ?? 0), 3), '0'), '.');
                        $taxRate    = (float)($item['tax_rate'] ?? 0);
                        $unitPrice  = (float)($item['unit_price'] ?? 0);
                        $upWithTax  = $unitPrice * (1 + $taxRate / 100);
                        $itemTotal  = (float)($item['total'] ?? 0);
                        $discAmt    = (float)($item['discount_amount'] ?? 0);
                        $discType   = $item['discount_type'] ?? '';
                        $discVal    = $item['discount_type_value'] ?? 0;
                        $discReason = $item['discount_reason'] ?? '';
                        $discLabel  = $discType === 'percentage'
                            ? $discVal . '%'
                            : '$' . number_format((float)$discVal, 0, ',', '.');
                    @endphp
                    <tr>
                        <td>
                            <div class="item-name">{{ $item['product_name'] }}</div>
                            @if(!empty($item['product_sku']))
                            <div class="item-meta">SKU: {{ $item['product_sku'] }}</div>
                            @endif
                            @if(!empty($item['import_code']))
                            <span class="import-badge">📄 {{ $item['import_code'] }}</span>
                            @endif
                            @if($discAmt > 0)
                            <div class="item-meta" style="color:#ef4444;">
                                Desc: {{ $discLabel }}
                                @if($discReason) ({{ $discReason }}) @endif
                                — -${{ number_format($discAmt, 0, ',', '.') }}
                            </div>
                            @endif
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            {{ $qty }}<br>
                            <span style="font-size:11px; color:var(--gray-500);">${{ number_format($upWithTax, 0, ',', '.') }}</span>
                        </td>
                        <td>${{ number_format($itemTotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>${{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                @if($taxTotal > 0)
                <div class="total-row">
                    <span>IVA</span>
                    <span>${{ number_format($taxTotal, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($itemDiscount > 0)
                <div class="total-row" style="color:#ef4444;">
                    <span>Descuento items</span>
                    <span>-${{ number_format($itemDiscount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($globalDiscountAmount > 0)
                @php
                    $globalDiscLabel = $globalDiscountType === 'percentage'
                        ? 'Desc. factura (' . rtrim(rtrim(number_format($globalDiscountValue, 2), '0'), '.') . '%)'
                        : 'Desc. factura';
                @endphp
                <div class="total-row" style="color:#ef4444;">
                    <span>{{ $globalDiscLabel }}</span>
                    <span>-${{ number_format($globalDiscountAmount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="total-row grand">
                    <span>TOTAL</span>
                    <span>${{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Payments --}}
            @if(!empty($payments))
            <div class="divider"></div>
            <div class="section-title">Forma de Pago</div>
            @php $totalPaid = array_sum(array_column($payments, 'amount')); @endphp
            @foreach($payments as $payment)
            <div class="payment-item">
                <span>{{ $payment['payment_method_name'] ?? 'N/A' }}</span>
                <span>${{ number_format((float)$payment['amount'], 0, ',', '.') }}</span>
            </div>
            @endforeach
            @if($totalPaid > $total)
            <div class="payment-item" style="background:#fef3c7; color:#92400e;">
                <span>Cambio</span>
                <span>${{ number_format($totalPaid - $total, 0, ',', '.') }}</span>
            </div>
            @endif
            @endif

            {{-- DIAN --}}
            @if(!empty($snap['is_electronic']) && !empty($snap['cufe']))
            <div class="divider"></div>
            <div class="dian-box">
                <div class="dian-title">✓ Validada por la DIAN</div>
                @if(!empty($snap['dian_number']))
                <p style="font-size:13px; color:#047857; margin-bottom:4px;">
                    DIAN Nro: <strong>{{ $snap['dian_number'] }}</strong>
                </p>
                @endif
                <div class="cufe-text">CUFE: {{ $snap['cufe'] }}</div>
            </div>
            @endif

            {{-- Seller --}}
            @if(!empty($snap['user_name']) || !empty($snap['cash_register_name']))
            <div class="divider"></div>
            <div style="font-size:13px; color:var(--gray-500);">
                @if(!empty($snap['user_name']))
                <span><strong>Atendido por:</strong> {{ $snap['user_name'] }}</span>&nbsp;
                @endif
                @if(!empty($snap['cash_register_name']))
                <span><strong>Caja:</strong> {{ $snap['cash_register_name'] }}</span>
                @endif
            </div>
            @endif

        </div>
    </div>

    {{-- Import Declarations --}}
    @if(!empty($imports))
    <div style="margin-bottom:12px; color:rgba(255,255,255,0.7); font-size:14px; font-weight:600; letter-spacing:0.3px;">
        📄 Documentos de Importación
    </div>

    @foreach($imports as $importItem)
    @php $importIdx = $loop->index; @endphp
    <div class="import-card">
        <div class="import-card-header">
            <div style="display:flex; align-items:center; gap:12px; flex:1;">
                <div class="icon">📦</div>
                <div class="info">
                    <h3>Declaración de Importación</h3>
                    <p>Código único de importación</p>
                </div>
            </div>
            <div class="import-code-tag">{{ $importItem['import_code'] }}</div>
        </div>
        <div class="import-card-body">
            @if(!empty($importItem['product_names']))
            <div class="section-title" style="margin-bottom:8px;">Productos incluidos</div>
            <div class="products-list">
                @foreach($importItem['product_names'] as $pname)
                <span class="product-tag">{{ $pname }}</span>
                @endforeach
            </div>
            @endif

            @if(!empty($importItem['file_path']))
            @php
                $pdfServeUrl = url('/sale-public-pdf/' . $snapshot->qr_token . '/' . $importIdx);
            @endphp

            {{-- Toggle button --}}
            <button
                type="button"
                class="pdf-toggle-btn"
                onclick="togglePdf({{ $importIdx }}, '{{ $pdfServeUrl }}')"
                id="btn-pdf-{{ $importIdx }}"
            >
                <span class="pdf-icon">📄</span>
                <span id="btn-label-{{ $importIdx }}">Ver declaración de importación</span>
                <svg id="btn-chevron-{{ $importIdx }}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition:transform .3s;"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            {{-- Inline PDF viewer --}}
            <div id="pdf-viewer-{{ $importIdx }}" class="pdf-viewer-wrap" style="display:none; background: #e5e7eb;">
                <div class="pdf-viewer-toolbar">
                    <span style="font-size:12px; opacity:.8;">Declaración – {{ $importItem['import_code'] }}</span>
                    <a href="{{ $pdfServeUrl }}" download class="pdf-toolbar-btn" title="Descargar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Descargar
                    </a>
                </div>
                <div id="pdf-render-{{ $importIdx }}" style="width: 100%; height: 680px; overflow-y: auto; padding: 16px 0; display:flex; flex-direction:column; align-items:center;"></div>
            </div>

            @else
            <p style="text-align:center; font-size:13px; color:var(--gray-500); padding:8px 0;">Documento no disponible</p>
            @endif
        </div>
    </div>
    @endforeach

    @else
    <div class="no-imports">Sin documentos de importación adjuntos</div>
    @endif

    <div class="page-footer">
        {{ $branch['name'] ?? 'MikPOS' }} &bull; Generado el {{ now()->format('d/m/Y H:i') }}<br>
        @if(!empty($branch['receipt_header'])){{ $branch['receipt_header'] }}@endif
    </div>

</div>

<script>
    const renderedPdfs = {};

    function togglePdf(idx, pdfUrl) {
        const viewer  = document.getElementById('pdf-viewer-' + idx);
        const label   = document.getElementById('btn-label-' + idx);
        const chevron = document.getElementById('btn-chevron-' + idx);
        const renderContainer = document.getElementById('pdf-render-' + idx);

        const isOpen = viewer.style.display !== 'none';

        if (isOpen) {
            viewer.style.display = 'none';
            label.textContent = 'Ver declaración de importación';
            chevron.style.transform = 'rotate(0deg)';
        } else {
            viewer.style.display = 'block';
            label.textContent = 'Cerrar visor';
            chevron.style.transform = 'rotate(180deg)';
            
            if (!renderedPdfs[idx]) {
                renderedPdfs[idx] = true;
                renderPDF(pdfUrl, renderContainer);
            }
        }
    }

    async function renderPDF(url, container) {
        container.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--gray-500); font-weight:600;">Cargando documento...<br><small style="opacity:0.6;">Por favor espera</small></div>';
        
        try {
            const loadingTask = pdfjsLib.getDocument(url);
            const pdf = await loadingTask.promise;
            container.innerHTML = ''; // clear loading
            
            for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                const page = await pdf.getPage(pageNum);
                const containerWidth = container.clientWidth || 320;
                
                const unscaledViewport = page.getViewport({ scale: 1 });
                // Target width slightly less than container for padding/scrollbar
                const targetWidth = containerWidth - 32; 
                const scale = targetWidth / unscaledViewport.width;
                const viewport = page.getViewport({ scale: scale });

                const canvas = document.createElement('canvas');
                canvas.style.display = 'block';
                canvas.style.margin = '0 auto 16px';
                canvas.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                canvas.style.backgroundColor = '#fff';
                
                const outputScale = window.devicePixelRatio || 1;
                canvas.width = Math.floor(viewport.width * outputScale);
                canvas.height = Math.floor(viewport.height * outputScale);
                canvas.style.width = Math.floor(viewport.width) + "px";
                canvas.style.height =  Math.floor(viewport.height) + "px";

                const transform = outputScale !== 1
                    ? [outputScale, 0, 0, outputScale, 0, 0]
                    : null;

                const context = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: context,
                    transform: transform,
                    viewport: viewport
                };

                container.appendChild(canvas);
                await page.render(renderContext).promise;
            }
        } catch (error) {
            console.error('Error rendering PDF:', error);
            container.innerHTML = '<div style="text-align:center; padding: 20px; color: #ef4444; font-weight:600;">No se pudo cargar la vista previa del documento.<br><br><a href="'+url+'" download style="display:inline-block; padding:8px 16px; background:#6366f1; color:#fff; text-decoration:none; border-radius:8px;">Descárgalo aquí</a></div>';
        }
    }
</script>

</body>
</html>
