<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $promotion->subject }}</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; -webkit-text-size-adjust: 100%; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #ff7261 0%, #a855f7 100%); padding: 32px 24px; text-align: center; }
        .header-logo { margin-bottom: 16px; }
        .header-logo img { max-width: 160px; max-height: 80px; object-fit: contain; border-radius: 8px; background: rgba(255,255,255,0.15); padding: 6px; }
        .header-brand { font-size: 13px; color: rgba(255,255,255,0.85); font-weight: 600; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; }
        .header-title { color: #ffffff; font-size: 24px; font-weight: 800; margin: 0; line-height: 1.3; }
        .body { padding: 32px 24px; }
        .greeting { font-size: 16px; color: #1e293b; margin: 0 0 20px; font-weight: 600; }
        .promo-image { width: 100%; border-radius: 12px; margin: 0 0 24px; display: block; max-height: 400px; object-fit: cover; }
        .message-content { font-size: 15px; color: #475569; line-height: 1.7; margin: 0 0 24px; }
        .message-content p { margin: 0 0 12px; }
        .message-content h1, .message-content h2, .message-content h3 { color: #1e293b; }
        .message-content ul, .message-content ol { padding-left: 20px; margin: 0 0 12px; }
        .message-content a { color: #a855f7; }
        .cta-container { text-align: center; margin: 28px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #ff7261 0%, #a855f7 100%); color: #ffffff !important; text-decoration: none; padding: 14px 36px; border-radius: 50px; font-size: 15px; font-weight: 700; letter-spacing: 0.5px; }
        .divider { border: none; border-top: 1px solid #f1f5f9; margin: 24px 0; }
        .footer { padding: 20px 24px; text-align: center; border-top: 1px solid #f1f5f9; background: #f8fafc; }
        .footer-brand { font-size: 14px; font-weight: 700; color: #334155; margin: 0 0 6px; }
        .footer-info { font-size: 12px; color: #94a3b8; margin: 3px 0; line-height: 1.5; }
        .footer-unsubscribe { font-size: 11px; color: #cbd5e1; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">

            {{-- Header --}}
            <div class="header">
                @if($branch && $branch->logo)
                <div class="header-logo">
                    <img src="{{ config('app.url') . \Illuminate\Support\Facades\Storage::url($branch->logo) }}" alt="{{ $branch->name }}">
                </div>
                @endif
                <div class="header-brand">{{ $branch->name ?? config('app.name') }}</div>
                <h1 class="header-title">{{ $promotion->subject }}</h1>
            </div>

            {{-- Body --}}
            <div class="body">
                <p class="greeting">Hola {{ $customer->full_name }},</p>

                {{-- Promotional image --}}
                @if($promotion->image_path)
                <img
                    src="{{ config('app.url') . \Illuminate\Support\Facades\Storage::url($promotion->image_path) }}"
                    alt="{{ $promotion->subject }}"
                    class="promo-image"
                >
                @endif

                {{-- Message content — admin-authored HTML --}}
                <div class="message-content">
                    {!! nl2br(e($promotion->message)) !!}
                </div>

                {{-- Optional CTA button --}}
                @if($promotion->button_text && $promotion->button_url)
                <div class="cta-container">
                    <a href="{{ $promotion->button_url }}" class="cta-button" target="_blank">
                        {{ $promotion->button_text }}
                    </a>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="footer">
                @if($branch)
                <p class="footer-brand">{{ $branch->name }}</p>
                @if($branch->address)
                <p class="footer-info">{{ $branch->address }}@if($branch->municipality), {{ $branch->municipality->name }}@endif</p>
                @endif
                @if($branch->phone)
                <p class="footer-info">Tel: {{ $branch->phone }}</p>
                @endif
                @endif
                <p class="footer-unsubscribe">Este correo fue enviado por {{ $branch->name ?? config('app.name') }}. Por favor no responda a este correo.</p>
            </div>

        </div>
    </div>
</body>
</html>
