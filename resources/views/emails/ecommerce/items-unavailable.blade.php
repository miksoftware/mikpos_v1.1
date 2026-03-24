@extends('emails.ecommerce.layout')

@section('title', "Pedido #{$sale->invoice_number} - Productos no disponibles")
@section('header-title', 'Actualización de tu pedido')
@section('header-subtitle', "Pedido #{$sale->invoice_number}")

@section('content')
    <p class="greeting">Hola {{ $customer->full_name }},</p>
    <p class="message">Te informamos que algunos productos de tu pedido no se encuentran disponibles en este momento:</p>

    <div class="alert-box alert-warning">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($unavailableItemNames as $itemName)
                <li style="margin-bottom: 4px;">{{ $itemName }}</li>
            @endforeach
        </ul>
    </div>

    <p class="message">Nuestro equipo está revisando tu pedido. Te notificaremos cuando sea procesado con los productos disponibles.</p>

@section('footer')
    @if($branch)
        <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">{{ $branch->name }} {{ $branch->phone ? '| Tel: ' . $branch->phone : '' }}</p>
    @endif
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">Este correo fue enviado automáticamente, por favor no responda.</p>
    <p style="font-size: 12px; color: #94a3b8; margin: 4px 0;">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
@endsection
@endsection
