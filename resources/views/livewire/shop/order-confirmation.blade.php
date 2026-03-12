<div>
    <div class="max-w-2xl mx-auto">
        {{-- Success Header --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Pedido confirmado</h1>
            <p class="text-slate-500">Tu pedido ha sido recibido y está pendiente de aprobación.</p>
        </div>

        {{-- Order Details --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Número de pedido</p>
                        <p class="text-lg font-bold text-slate-900">{{ $sale->invoice_number }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                        Pendiente de aprobación
                    </span>
                </div>
            </div>

            {{-- Items --}}
            <div class="px-6 py-4">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Productos</h3>
                <div class="space-y-3">
                    @foreach($sale->items as $item)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex-1">
                                <span class="font-medium text-slate-900">{{ $item->product_name }}</span>
                                <span class="text-slate-500 ml-1">x{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</span>
                            </div>
                            <span class="font-medium text-slate-900">${{ number_format($item->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Totals --}}
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="text-slate-900">${{ number_format($sale->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Impuestos</span>
                        <span class="text-slate-900">${{ number_format($sale->tax_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-base pt-2 border-t border-slate-200">
                        <span class="text-slate-900">Total</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff7261] to-[#a855f7]">${{ number_format($sale->total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment --}}
            @if($sale->payments->isNotEmpty())
            <div class="px-6 py-4 border-t border-slate-200">
                <h3 class="text-sm font-semibold text-slate-700 mb-2">Método de pago</h3>
                @foreach($sale->payments as $payment)
                    <p class="text-sm text-slate-600">{{ $payment->paymentMethod->name ?? 'N/A' }}</p>
                @endforeach
            </div>
            @endif

            {{-- Shipping --}}
            @if($sale->ecommerceOrder)
                @php $order = $sale->ecommerceOrder; @endphp
                @if($order->shipping_address || $order->shipping_phone)
                <div class="px-6 py-4 border-t border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2">Dirección de envío</h3>
                    <div class="text-sm text-slate-600 space-y-1">
                        @if($order->shipping_address)
                            <p>{{ $order->shipping_address }}</p>
                        @endif
                        @if($order->shippingMunicipality || $order->shippingDepartment)
                            <p>{{ $order->shippingMunicipality?->name }}{{ $order->shippingDepartment ? ', ' . $order->shippingDepartment->name : '' }}</p>
                        @endif
                        @if($order->shipping_phone)
                            <p>Tel: {{ $order->shipping_phone }}</p>
                        @endif
                    </div>
                </div>
                @endif
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-6">
            <a href="{{ route('shop.orders') }}" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                Ver mis pedidos
            </a>
            <a href="{{ route('shop.catalog') }}" class="px-6 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-all">
                Seguir comprando
            </a>
        </div>
    </div>
</div>
