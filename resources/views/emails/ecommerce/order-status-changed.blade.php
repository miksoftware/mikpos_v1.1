@extends('emails.ecommerce.layout')

@section('title', "Pedido #{$sale->invoice_number} - Actualización")
@section('header-title', $newStatus === 'completed' ? '¡Pedido aprobado!' : 'Pedido rechazado')
@section('header-subtitle', "Pedido #{$sale->invoice_number}")

@section('content')
    <p class="greeting">Hola {{ $customer->full_name }},</p>

    @if($newStatus === 'completed')
        <p class="message">Tu pedido ha sido aprobado y está siendo preparado.</p>

        <div style="text-align: center; margin: 20px 0;">
            <span class="status-badge status-approved">✓ Aprobado</span>
        </div>

        {{-- Show items --}}
        <table class="table-container" width="100%">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    @if(!$item->is_unavailable)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                        <td class="text-right">${{ number_format($item->total, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        {{-- Unavailable items notice --}}
        @php $unavailable = $sale->items->where('is_unavailable', true); @endphp
        @if($unavailable->isNotEmpty())
            <div class="alert-box alert-warning">
                <strong>Productos no disponibles:</strong>
                <ul style="margin: 8px 0 0; padding-left: 20px;">
                    @foreach($unavailable as $item)
                        <li>{{ $item->product_name }} {{ $item->unavailable_reason ? '- ' . $item->unavailable_reason : '' }}</li>
                    @endforeach
                </ul>
                <p style="margin-top: 8px;">El total de tu pedido ha sido ajustado.</p>
            </div>
        @endif

        <div class="totals">
            <table width="100%" style="border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0 4px; font-size: 16px; font-weight: 700; color: #1e293b;">Total</td>
                    <td style="padding: 8px 0 4px; font-size: 16px; font-weight: 700; color: #1e293b; text-align: right;">${{ number_format($sale->total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

    @else
        <p class="message">Lamentamos informarte que tu pedido ha sido rechazado.</p>

        <div style="text-align: center; margin: 20px 0;">
            <span class="status-badge status-rejected">✗ Rechazado</span>
        </div>

        @if($reason)
        <div class="alert-box alert-danger">
            <strong>Motivo:</strong> {{ $reason }}
        </div>
        @endif

        <p class="message">Si tienes alguna duda, no dudes en contactarnos.</p>
    @endif

@section('footer')
    @if($branch)
        <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">{{ $branch->name }} {{ $branch->phone ? '| Tel: ' . $branch->phone : '' }}</p>
    @endif
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">Este correo fue enviado automáticamente, por favor no responda.</p>
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
@endsection
@endsection
