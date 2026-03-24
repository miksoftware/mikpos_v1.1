@extends('emails.ecommerce.layout')

@section('title', "Pedido #{$sale->invoice_number} recibido")
@section('header-title', '¡Pedido recibido!')
@section('header-subtitle', "Pedido #{$sale->invoice_number}")

@section('content')
    <p class="greeting">Hola {{ $customer->full_name }},</p>
    <p class="message">Hemos recibido tu pedido correctamente. Nuestro equipo lo revisará y te notificaremos cuando sea aprobado.</p>

    {{-- Order items --}}
    <table class="table-container" width="100%">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td class="text-right">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">${{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td style="padding: 4px 0; font-size: 14px; color: #475569;">Subtotal</td>
                <td style="padding: 4px 0; font-size: 14px; color: #475569; text-align: right;">${{ number_format($sale->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($sale->tax_total > 0)
            <tr>
                <td style="padding: 4px 0; font-size: 14px; color: #475569;">Impuestos</td>
                <td style="padding: 4px 0; font-size: 14px; color: #475569; text-align: right;">${{ number_format($sale->tax_total, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 8px 0 4px; font-size: 16px; font-weight: 700; color: #1e293b; border-top: 1px solid #e2e8f0;">Total</td>
                <td style="padding: 8px 0 4px; font-size: 16px; font-weight: 700; color: #1e293b; border-top: 1px solid #e2e8f0; text-align: right;">${{ number_format($sale->total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Payment method --}}
    @if($sale->payments->isNotEmpty())
    <div class="info-box">
        <h3>Método de pago</h3>
        <p>{{ $sale->payments->first()->paymentMethod->name ?? 'N/A' }}</p>
    </div>
    @endif

    {{-- Shipping info --}}
    @if($order)
    <div class="info-box">
        <h3>Datos de envío</h3>
        @if($order->shipping_address)
            <p>{{ $order->shipping_address }}</p>
        @endif
        @if($order->shippingMunicipality || $order->shippingDepartment)
            <p>{{ $order->shippingMunicipality?->name }}, {{ $order->shippingDepartment?->name }}</p>
        @endif
        @if($order->shipping_phone)
            <p>Tel: {{ $order->shipping_phone }}</p>
        @endif
        @if($order->customer_notes)
            <p>Notas: {{ $order->customer_notes }}</p>
        @endif
    </div>
    @endif

    <div class="alert-box alert-success">
        <strong>Estado:</strong> Pendiente de aprobación. Te enviaremos un correo cuando tu pedido sea procesado.
    </div>

@section('footer')
    @if($branch)
        <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">{{ $branch->name }} {{ $branch->phone ? '| Tel: ' . $branch->phone : '' }}</p>
    @endif
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">Este correo fue enviado automáticamente, por favor no responda.</p>
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
@endsection
@endsection
