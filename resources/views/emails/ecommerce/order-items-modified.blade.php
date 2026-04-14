@extends('emails.ecommerce.layout')

@section('title', "Pedido #{$sale->invoice_number} - Cambio de cantidades")
@section('header-title', 'Actualización de tu pedido')
@section('header-subtitle', "Pedido #{$sale->invoice_number}")

@section('content')
    <p class="greeting">Hola {{ $customer->full_name }},</p>
    <p class="message">Te informamos que hemos realizado algunos cambios en las cantidades de tu pedido:</p>

    <table class="table-container" width="100%" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Cant. anterior</th>
                <th class="text-right">Cant. nueva</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($changes as $change)
            <tr>
                <td>{{ $change['product_name'] }}</td>
                <td class="text-right" style="text-decoration: line-through; color: #ef4444;">{{ rtrim(rtrim(number_format($change['old_quantity'], 3), '0'), '.') }}</td>
                <td class="text-right" style="font-weight: 600; color: #166534;">{{ rtrim(rtrim(number_format($change['new_quantity'], 3), '0'), '.') }}</td>
                <td style="font-size: 13px; color: #64748b;">{{ $change['reason'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding: 4px 0; font-size: 14px; color: #475569;">Subtotal</td>
                <td style="padding: 4px 0; font-size: 14px; color: #475569; text-align: right;">${{ number_format($sale->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if((float) $sale->tax_total > 0)
            <tr>
                <td style="padding: 4px 0; font-size: 14px; color: #475569;">Impuestos</td>
                <td style="padding: 4px 0; font-size: 14px; color: #475569; text-align: right;">${{ number_format($sale->tax_total, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 8px 0 4px; font-size: 16px; font-weight: 700; color: #1e293b; border-top: 1px solid #e2e8f0;">Nuevo Total</td>
                <td style="padding: 8px 0 4px; font-size: 16px; font-weight: 700; color: #1e293b; border-top: 1px solid #e2e8f0; text-align: right;">${{ number_format($sale->total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <p class="message">Si tienes alguna duda sobre estos cambios, no dudes en contactarnos.</p>

@section('footer')
    @if($branch)
        <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">{{ $branch->name }} {{ $branch->phone ? '| Tel: ' . $branch->phone : '' }}</p>
    @endif
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">Este correo fue enviado automáticamente, por favor no responda.</p>
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
@endsection
@endsection
