<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Mis Pedidos</h1>
        <p class="text-sm text-slate-500 mt-1">Consulta el estado de tus compras.</p>
    </div>

    @if($orders->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
            <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">No tienes pedidos aún</h3>
            <p class="text-slate-500 mb-4">Explora nuestro catálogo y realiza tu primera compra.</p>
            <a href="{{ route('shop.catalog') }}" class="inline-flex px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                Ver catálogo
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Orders List --}}
            <div class="lg:col-span-{{ $selectedSale ? '1' : '3' }}">
                <div class="space-y-3">
                    @foreach($orders as $order)
                        <button wire:click="viewOrder({{ $order->id }})" class="w-full text-left bg-white rounded-2xl shadow-sm border transition-all
                            {{ $selectedSale && $selectedSale->id === $order->id ? 'border-[#a855f7] ring-2 ring-[#a855f7]/20' : 'border-slate-200 hover:border-slate-300' }}">
                            <div class="px-5 py-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-slate-900">{{ $order->invoice_number }}</span>
                                    @php
                                        $ecoStatus = $order->ecommerceOrder?->status;
                                        $statusConfig = match(true) {
                                            $ecoStatus === 'partial' => ['label' => 'Parcial', 'class' => 'bg-orange-100 text-orange-800'],
                                            $ecoStatus === 'rejected' || $order->status === 'rejected' => ['label' => 'Rechazado', 'class' => 'bg-red-100 text-red-800'],
                                            $order->status === 'pending_approval' => ['label' => 'Pendiente', 'class' => 'bg-amber-100 text-amber-800'],
                                            $order->status === 'completed' => ['label' => 'Aprobado', 'class' => 'bg-green-100 text-green-800'],
                                            default => ['label' => ucfirst($order->status), 'class' => 'bg-slate-100 text-slate-800'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusConfig['class'] }}">
                                        {{ $statusConfig['label'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                    <span class="font-semibold text-slate-900">${{ number_format($order->total, 0, ',', '.') }}</span>
                                </div>
                                @if($order->payments->isNotEmpty())
                                    <p class="text-xs text-slate-400 mt-1">{{ $order->payments->first()->paymentMethod->name ?? '' }}</p>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            </div>

            {{-- Order Detail Panel --}}
            @if($selectedSale)
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-24">
                        {{-- Detail Header --}}
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                            <div>
                                <p class="text-sm text-slate-500">Pedido</p>
                                <p class="text-lg font-bold text-slate-900">{{ $selectedSale->invoice_number }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                @php
                                    $detailEcoStatus = $selectedSale->ecommerceOrder?->status;
                                    $detailStatus = match(true) {
                                        $detailEcoStatus === 'partial' => ['label' => 'Enviado parcialmente', 'class' => 'bg-orange-100 text-orange-800'],
                                        $detailEcoStatus === 'rejected' || $selectedSale->status === 'rejected' => ['label' => 'Rechazado', 'class' => 'bg-red-100 text-red-800'],
                                        $selectedSale->status === 'pending_approval' => ['label' => 'Pendiente de aprobación', 'class' => 'bg-amber-100 text-amber-800'],
                                        $selectedSale->status === 'completed' => ['label' => 'Aprobado', 'class' => 'bg-green-100 text-green-800'],
                                        default => ['label' => ucfirst($selectedSale->status), 'class' => 'bg-slate-100 text-slate-800'],
                                    };
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $detailStatus['class'] }}">
                                    {{ $detailStatus['label'] }}
                                </span>
                                <button wire:click="closeDetail" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[calc(100vh-12rem)] overflow-y-auto">
                            {{-- Date --}}
                            <div class="px-6 py-3 text-sm text-slate-500 border-b border-slate-100">
                                Fecha: {{ $selectedSale->created_at->format('d/m/Y H:i') }}
                            </div>

                            {{-- Partial Order Notice --}}
                            @if($selectedSale->ecommerceOrder?->status === 'partial')
                                <div class="px-6 py-3 bg-orange-50 border-b border-orange-100">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-orange-800">Pedido enviado parcialmente</p>
                                            <p class="text-sm text-orange-700 mt-0.5">Algunos productos no pudieron ser enviados. Revisa los detalles a continuación.</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Rejection Reason --}}
                            @if($selectedSale->status === 'rejected' && $selectedSale->ecommerceOrder?->rejection_reason)
                                <div class="px-6 py-3 bg-red-50 border-b border-red-100">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-red-800">Motivo de rechazo</p>
                                            <p class="text-sm text-red-700 mt-0.5">{{ $selectedSale->ecommerceOrder->rejection_reason }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Products --}}
                            <div class="px-6 py-4">
                                <h3 class="text-sm font-semibold text-slate-700 mb-3">Productos</h3>
                                <div class="space-y-3">
                                    @foreach($selectedSale->items as $item)
                                        <div class="flex items-center justify-between text-sm {{ $item->is_unavailable ? 'opacity-60' : '' }}">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="font-medium text-slate-900 truncate {{ $item->is_unavailable ? 'line-through' : '' }}">{{ $item->product_name }}</p>
                                                    @if($item->is_unavailable)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 flex-shrink-0">No enviado</span>
                                                    @endif
                                                </div>
                                                <p class="text-slate-500">${{ number_format($item->unit_price, 0, ',', '.') }} x {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</p>
                                                @if($item->is_unavailable && $item->unavailable_reason)
                                                    <p class="text-xs text-orange-600 mt-0.5">Motivo: {{ $item->unavailable_reason }}</p>
                                                @endif
                                            </div>
                                            <span class="font-medium text-slate-900 ml-4 {{ $item->is_unavailable ? 'line-through' : '' }}">${{ number_format($item->total, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Totals --}}
                            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Subtotal</span>
                                        <span class="text-slate-900">${{ number_format($selectedSale->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Impuestos</span>
                                        <span class="text-slate-900">${{ number_format($selectedSale->tax_total, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between font-bold text-base pt-2 border-t border-slate-200">
                                        <span class="text-slate-900">Total</span>
                                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff7261] to-[#a855f7]">${{ number_format($selectedSale->total, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Payment --}}
                            @if($selectedSale->payments->isNotEmpty())
                                <div class="px-6 py-4 border-t border-slate-200">
                                    <h3 class="text-sm font-semibold text-slate-700 mb-2">Método de pago</h3>
                                    @foreach($selectedSale->payments as $payment)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-slate-600">{{ $payment->paymentMethod->name ?? 'N/A' }}</span>
                                            <span class="text-slate-900">${{ number_format($payment->amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Shipping --}}
                            @if($selectedSale->ecommerceOrder)
                                @php $ecoOrder = $selectedSale->ecommerceOrder; @endphp
                                @if($ecoOrder->shipping_address || $ecoOrder->shipping_phone)
                                    <div class="px-6 py-4 border-t border-slate-200">
                                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Dirección de envío</h3>
                                        <div class="text-sm text-slate-600 space-y-1">
                                            @if($ecoOrder->shipping_address)
                                                <p>{{ $ecoOrder->shipping_address }}</p>
                                            @endif
                                            @if($ecoOrder->shippingMunicipality || $ecoOrder->shippingDepartment)
                                                <p>{{ $ecoOrder->shippingMunicipality?->name }}{{ $ecoOrder->shippingDepartment ? ', ' . $ecoOrder->shippingDepartment->name : '' }}</p>
                                            @endif
                                            @if($ecoOrder->shipping_phone)
                                                <p>Tel: {{ $ecoOrder->shipping_phone }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($ecoOrder->customer_notes)
                                    <div class="px-6 py-4 border-t border-slate-200">
                                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Notas</h3>
                                        <p class="text-sm text-slate-600">{{ $ecoOrder->customer_notes }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
