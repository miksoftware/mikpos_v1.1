@extends('emails.ecommerce.layout')

@section('title', "Pedido #{$sale->invoice_number} - Cambio de método de pago")
@section('header-title', 'Actualización de método de pago')
@section('header-subtitle', "Pedido #{$sale->invoice_number}")

@section('content')
    <p class="greeting">Hola {{ $customer ? $customer->full_name : 'Cliente' }},</p>

    <p class="message">
        Te informamos que se ha actualizado el método de pago registrado para tu pedido <strong>#{{ $sale->invoice_number }}</strong>.
    </p>

    <div class="info-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0;">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #64748b; font-size: 14px; width: 45%;">Método de pago anterior:</td>
                <td style="padding: 8px 0; color: #94a3b8; font-size: 14px; font-weight: 600; text-decoration: line-through;">
                    {{ $oldPaymentMethod }}
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #64748b; font-size: 14px;">Nuevo método de pago:</td>
                <td style="padding: 8px 0; color: #166534; font-size: 15px; font-weight: 700;">
                    ✓ {{ $newPaymentMethod }}
                </td>
            </tr>
            <tr>
                <td style="padding: 12px 0 4px; color: #1e293b; font-size: 14px; font-weight: 600; border-top: 1px solid #e2e8f0;">Total a pagar:</td>
                <td style="padding: 12px 0 4px; color: #ff7261; font-size: 16px; font-weight: 700; border-top: 1px solid #e2e8f0;">
                    ${{ number_format($sale->total, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($reason))
        <div class="alert-box alert-warning">
            <strong>Nota / Motivo:</strong> {{ $reason }}
        </div>
    @endif

    <p class="message" style="margin-top: 20px; font-size: 13px; color: #64748b;">
        Si tienes alguna pregunta sobre este cambio o necesitas asistencia con tu pago, no dudes en comunicarte con nuestro equipo.
    </p>
@endsection

@section('footer')
    @if($branch)
        <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">{{ $branch->name }} {{ $branch->phone ? '| Tel: ' . $branch->phone : '' }}</p>
    @endif
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">Este correo fue enviado automáticamente, por favor no responda.</p>
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
@endsection
