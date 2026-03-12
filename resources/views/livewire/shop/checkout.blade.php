<div>
    <div class="mb-6">
        <a href="{{ route('shop.cart') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver al carrito
        </a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Checkout</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Shipping & Payment --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer Info --}}
            @php $customer = Auth::guard('customer')->user(); @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Datos del cliente</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-500">Nombre:</span>
                        <span class="ml-1 font-medium text-slate-900">{{ $customer->full_name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Documento:</span>
                        <span class="ml-1 font-medium text-slate-900">{{ $customer->document_number }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500">Email:</span>
                        <span class="ml-1 font-medium text-slate-900">{{ $customer->email }}</span>
                    </div>
                    @if($customer->phone)
                    <div>
                        <span class="text-slate-500">Teléfono:</span>
                        <span class="ml-1 font-medium text-slate-900">{{ $customer->phone }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Shipping Address --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Dirección de envío</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Departamento</label>
                            <x-searchable-select
                                wire:model.live="department_id"
                                :options="$departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->toArray()"
                                placeholder="Seleccionar departamento..."
                                searchPlaceholder="Buscar departamento..."
                            />
                            @error('department_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Municipio</label>
                            <x-searchable-select
                                wire:model="municipality_id"
                                :options="$municipalities->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->toArray()"
                                placeholder="Seleccionar municipio..."
                                searchPlaceholder="Buscar municipio..."
                                :disabled="!$department_id"
                            />
                            @error('municipality_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                        <input type="text" wire:model="address" placeholder="Dirección de envío"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                        @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono de contacto</label>
                            <input type="text" wire:model="phone" placeholder="Teléfono"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                            @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                            <input type="text" wire:model="notes" placeholder="Notas adicionales (opcional)"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                            @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Method --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Método de pago</h2>
                @error('payment_method_id') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($paymentMethods as $method)
                        <label
                            class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all
                                {{ $payment_method_id == $method->id ? 'border-[#ff7261] bg-[#ff7261]/5' : 'border-slate-200 hover:border-slate-300' }}"
                        >
                            <input type="radio" wire:model.live="payment_method_id" value="{{ $method->id }}" class="sr-only">
                            <div class="w-8 h-8 rounded-lg {{ $payment_method_id == $method->id ? 'bg-gradient-to-br from-[#ff7261] to-[#a855f7]' : 'bg-slate-100' }} flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 {{ $payment_method_id == $method->id ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium {{ $payment_method_id == $method->id ? 'text-slate-900' : 'text-slate-600' }}">{{ $method->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: Order Summary --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Resumen del pedido</h2>

                <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                    @foreach($items as $item)
                        <div class="flex items-center gap-3">
                            @if($item['image'])
                                <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['name'] }}" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ $item['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $item['quantity'] }} x ${{ number_format($item['unit_price'], 0, ',', '.') }}</p>
                            </div>
                            <span class="text-sm font-medium text-slate-900">${{ number_format($item['unit_price'] * $item['quantity'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-200 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="text-slate-900">${{ number_format($this->subtotal - $this->taxTotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Impuestos</span>
                        <span class="text-slate-900">${{ number_format($this->taxTotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold pt-2 border-t border-slate-200">
                        <span class="text-slate-900">Total</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff7261] to-[#a855f7]">${{ number_format($this->total, 0, ',', '.') }}</span>
                    </div>
                </div>

                <button wire:click="placeOrder" wire:loading.attr="disabled"
                    class="w-full mt-6 px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="placeOrder">Confirmar Pedido</span>
                    <span wire:loading wire:target="placeOrder" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Procesando...
                    </span>
                </button>

                <p class="text-xs text-slate-400 text-center mt-3">Tu pedido quedará pendiente de aprobación por el administrador.</p>
            </div>
        </div>
    </div>
</div>
