<div class="h-[calc(100vh-4rem)] flex flex-col lg:flex-row gap-4 p-4 bg-slate-100">
    {{-- Left Panel: Product Search & Cart --}}
    <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-[#ff7261]/10 to-[#a855f7]/10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('purchases') }}" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-white rounded-lg transition-colors" title="Volver al listado">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">
                            @if($isEditing)
                                Editar Compra {{ $purchase->purchase_number }}
                            @else
                                Nueva Compra
                            @endif
                        </h1>
                        <p class="text-sm text-slate-500">
                            @if($isCompletedEdit)
                                <span class="text-amber-600 font-medium">⚠️ Editando compra completada</span>
                            @else
                                Registra la compra de productos
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Warning for completed edit --}}
        @if($isCompletedEdit)
        <div class="px-6 py-3 bg-amber-50 border-b border-amber-200">
            <div class="flex items-center gap-2 text-amber-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span class="text-sm font-medium">Los cambios en esta compra afectarán el inventario de los productos.</span>
            </div>
        </div>
        @endif

        {{-- Product Search --}}
        <div class="px-6 py-4 border-b border-slate-100">
            @if($needsBranchSelection && !$branch_id)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-sm text-amber-700">Selecciona una sucursal en el panel derecho para buscar productos</p>
            </div>
            @else
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input 
                    wire:model.live.debounce.300ms="productSearch" 
                    wire:keydown.enter="addProductByBarcode"
                    type="text" 
                    class="block w-full pl-12 pr-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-lg" 
                    placeholder="Buscar producto por nombre, SKU o código de barras..."
                    autofocus>
                
                {{-- Search Results Dropdown --}}
                @if(count($searchResults) > 0)
                <div class="absolute z-50 w-full mt-2 bg-white border border-slate-200 rounded-xl shadow-xl max-h-80 overflow-y-auto">
                    @foreach($searchResults as $result)
                    <button type="button" wire:click="addProduct({{ $result['id'] }})" class="w-full px-4 py-3 flex items-center gap-4 hover:bg-gradient-to-r hover:from-[#ff7261]/5 hover:to-[#a855f7]/5 transition-colors text-left border-b border-slate-100 last:border-0">
                        @if($result['image'])
                        <img src="{{ Storage::url($result['image']) }}" class="w-12 h-12 rounded-lg object-cover">
                        @else
                        <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800 truncate">{{ $result['name'] }}</p>
                            <p class="text-sm text-slate-500">
                                @if($result['sku'])SKU: {{ $result['sku'] }} · @endif
                                @if($result['category']){{ $result['category'] }} · @endif
                                Stock: {{ $result['current_stock'] }} {{ $result['unit'] }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-[#ff7261]">${{ number_format($result['purchase_price'], 2) }}</span>
                            <p class="text-xs text-slate-400">Costo actual</p>
                        </div>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Cart Items --}}
        <div class="flex-1 overflow-y-auto px-6 py-4">
            @if(count($cartItems) > 0)
            <div class="space-y-3">
                @foreach($cartItems as $index => $item)
                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors">
                    @if($item['image'])
                    <img src="{{ Storage::url($item['image']) }}" class="w-14 h-14 rounded-lg object-cover">
                    @else
                    <div class="w-14 h-14 rounded-lg bg-slate-200 flex items-center justify-center">
                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    @endif
                    
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-800 truncate">{{ $item['name'] }}</p>
                        <p class="text-sm text-slate-500">{{ $item['sku'] ?? 'Sin SKU' }} · {{ $item['unit'] }}</p>
                    </div>

                    {{-- Quantity --}}
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})" class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                        </button>
                        <input type="number" wire:change="updateQuantity({{ $index }}, $event.target.value)" value="{{ $item['quantity'] }}" min="1" class="w-16 text-center py-1.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        <button type="button" wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})" class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>

                    {{-- Unit Cost --}}
                    <div class="w-24">
                        <label class="text-xs text-slate-500 block mb-1">Costo</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400 text-sm">$</span>
                            <input type="number" wire:change="updateUnitCost({{ $index }}, $event.target.value)" value="{{ $item['unit_cost'] }}" step="0.01" min="0" class="w-full pl-6 pr-1 py-1.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-right text-sm">
                        </div>
                    </div>

                    {{-- Sale Price --}}
                    <div class="w-24">
                        <label class="text-xs text-slate-500 block mb-1">P. Venta</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400 text-sm">$</span>
                            <input type="number" wire:change="updateSalePrice({{ $index }}, $event.target.value)" value="{{ $item['sale_price'] ?? 0 }}" step="0.01" min="0" class="w-full pl-6 pr-1 py-1.5 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500/50 focus:border-green-500 text-right text-sm bg-green-50">
                        </div>
                    </div>

                    {{-- Subtotal --}}
                    <div class="w-24 text-right">
                        <p class="text-xs text-slate-500">Subtotal</p>
                        <p class="font-bold text-slate-800">${{ number_format($item['subtotal'], 2) }}</p>
                    </div>

                    {{-- Remove --}}
                    <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
                @endforeach
            </div>
            @else
            <div class="h-full flex flex-col items-center justify-center text-slate-400">
                <svg class="w-20 h-20 mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <p class="text-lg font-medium">Carrito vacío</p>
                <p class="text-sm">Busca productos para agregarlos a la compra</p>
            </div>
            @endif
        </div>

        {{-- Cart Footer --}}
        @if(count($cartItems) > 0)
        <div class="px-6 py-3 border-t border-slate-200 bg-slate-50">
            <div class="flex items-center justify-between">
                <button type="button" wire:click="clearCart" class="text-sm text-red-500 hover:text-red-600 font-medium">
                    Vaciar carrito
                </button>
                <span class="text-sm text-slate-500">{{ count($cartItems) }} producto(s)</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Right Panel: Purchase Details & Summary --}}
    <div class="w-full lg:w-96 flex flex-col gap-4 overflow-y-auto">
        {{-- Supplier & Details --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Datos de la Compra
            </h3>
            
            <div class="space-y-4">
                {{-- Branch Selector for Super Admin --}}
                @if($needsBranchSelection)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-xs font-medium text-amber-800">Selecciona la sucursal</p>
                            <select wire:model="branch_id" class="mt-1.5 w-full px-3 py-2 border border-amber-300 rounded-lg bg-white focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm @error('branch_id') border-red-300 @enderror">
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Proveedor *</label>
                    <select wire:model="supplier_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] @error('supplier_id') border-red-300 @enderror">
                        <option value="">Seleccionar proveedor...</option>
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de Compra *</label>
                    <input wire:model="purchase_date" type="date" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Factura Proveedor</label>
                    <input wire:model="supplier_invoice" type="text" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Número de factura">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                    <textarea wire:model="notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Observaciones..."></textarea>
                </div>
            </div>
        </div>

        {{-- Payment Type --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Tipo de Pago
            </h3>

            <div class="space-y-4">
                {{-- Payment Type Toggle --}}
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('payment_type', 'cash')" class="flex-1 py-2.5 px-4 rounded-xl font-medium transition-all {{ $payment_type === 'cash' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Contado
                    </button>
                    <button type="button" wire:click="$set('payment_type', 'credit')" class="flex-1 py-2.5 px-4 rounded-xl font-medium transition-all {{ $payment_type === 'credit' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Crédito
                    </button>
                </div>

                @if($payment_type === 'cash')
                {{-- Cash Payment --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Método de Pago *</label>
                    <select wire:model="payment_method_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] @error('payment_method_id') border-red-300 @enderror">
                        <option value="">Seleccionar...</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                    @error('payment_method_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                @else
                {{-- Credit Payment --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Monto del Crédito *</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">$</span>
                        <input wire:model="credit_amount" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] @error('credit_amount') border-red-300 @enderror">
                    </div>
                    @error('credit_amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de Pago *</label>
                    <input wire:model="payment_due_date" type="date" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] @error('payment_due_date') border-red-300 @enderror">
                    @error('payment_due_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="pt-3 border-t border-slate-200">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Abono Realizado</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">$</span>
                        <input wire:model="paid_amount" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                    </div>
                </div>

                @if($paid_amount > 0)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Método de Pago del Abono</label>
                    <select wire:model="partial_payment_method_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        <option value="">Seleccionar...</option>
                        @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 bg-blue-50 rounded-xl">
                    <div class="flex justify-between text-sm">
                        <span class="text-blue-600">Saldo pendiente:</span>
                        <span class="font-bold text-blue-700">${{ number_format(($credit_amount ?? 0) - $paid_amount, 2) }}</span>
                    </div>
                </div>
                @endif
                @endif
            </div>
        </div>

        {{-- Summary --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Resumen
            </h3>

            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Subtotal</span>
                    <span class="font-medium text-slate-700">${{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Impuestos</span>
                    <span class="font-medium text-slate-700">${{ number_format($taxAmount, 2) }}</span>
                </div>
                @if($discountAmount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Descuentos</span>
                    <span class="font-medium text-green-600">-${{ number_format($discountAmount, 2) }}</span>
                </div>
                @endif
                <div class="pt-3 border-t border-slate-200">
                    <div class="flex justify-between">
                        <span class="text-lg font-semibold text-slate-800">Total</span>
                        <span class="text-2xl font-bold text-[#ff7261]">${{ number_format($total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="space-y-3">
            <button 
                wire:click="completePurchase" 
                @if(count($cartItems) === 0) disabled @endif
                class="w-full py-4 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                <span wire:loading.remove wire:target="completePurchase">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ $isEditing ? 'Guardar Cambios' : 'Completar Compra' }}
                </span>
                <span wire:loading wire:target="completePurchase">Procesando...</span>
            </button>

            @if(!$isCompletedEdit)
            <button 
                wire:click="saveDraft" 
                @if(count($cartItems) === 0) disabled @endif
                class="w-full py-3 bg-white border-2 border-slate-200 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="saveDraft">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Guardar Borrador
                </span>
                <span wire:loading wire:target="saveDraft">Guardando...</span>
            </button>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-purchase', (event) => {
                const purchaseId = event.purchaseId;
                const url = `/purchase-receipt/${purchaseId}`;
                window.open(url, '_blank', 'width=800,height=600');
            });
        });
    </script>
</div>
