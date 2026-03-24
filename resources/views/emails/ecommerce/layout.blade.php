<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #ff7261, #a855f7); padding: 24px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 20px; margin: 0; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.9); font-size: 14px; margin: 8px 0 0; }
        .body { padding: 24px; }
        .greeting { font-size: 16px; color: #1e293b; margin: 0 0 16px; }
        .message { font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 20px; }
        .table-container { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .table-container th { background: #f8fafc; padding: 10px 12px; text-align: left; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .table-container td { padding: 10px 12px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .table-container .text-right { text-align: right; }
        .totals { background: #f8fafc; border-radius: 12px; padding: 16px; margin: 16px 0; }
        .totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; color: #475569; }
        .totals-row.total { font-weight: 700; font-size: 16px; color: #1e293b; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px; }
        .info-box { background: #f8fafc; border-radius: 12px; padding: 16px; margin: 16px 0; }
        .info-box h3 { font-size: 14px; color: #1e293b; margin: 0 0 8px; font-weight: 600; }
        .info-box p { font-size: 13px; color: #64748b; margin: 4px 0; line-height: 1.5; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-partial { background: #dbeafe; color: #1e40af; }
        .alert-box { border-radius: 12px; padding: 16px; margin: 16px 0; font-size: 14px; line-height: 1.5; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .footer { padding: 20px 24px; text-align: center; border-top: 1px solid #f1f5f9; }
        .footer p { font-size: 12px; color: #94a3b8; margin: 4px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>@yield('header-title')</h1>
                <p>@yield('header-subtitle', '')</p>
            </div>
            <div class="body">
                @yield('content')
            </div>
            <div class="footer">
                @hasSection('footer')
                    @yield('footer')
                @else
                    <p>Este correo fue enviado automáticamente, por favor no responda.</p>
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
