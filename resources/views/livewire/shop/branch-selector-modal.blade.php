<div>
    @if($isOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
                
                @if($showCartWarning)
                    {{-- Cart Warning Modal Content --}}
                    <div class="p-6">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 mb-4 border border-amber-200/60">
                            <svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 text-center mb-2">¿Cambiar de sucursal?</h3>
                        <p class="text-sm text-slate-600 text-center mb-6">
                            Tienes productos agregados en tu carrito. Si cambias a <strong class="text-slate-900">{{ $pendingBranchName }}</strong>, tu carrito actual se vaciará para cargar el catálogo y stock de la nueva sede.
                        </p>

                        <div class="flex flex-col-reverse sm:flex-row gap-3">
                            <button type="button"
                                wire:click="cancelSwitch"
                                class="w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
                                Seguir en sede actual
                            </button>
                            <button type="button"
                                wire:click="confirmBranchSwitch"
                                class="w-full justify-center rounded-xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                                Sí, cambiar de sede
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Branch Selection List --}}
                    <div class="p-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center text-[#ff7261]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">Selecciona tu sucursal</h3>
                                    <p class="text-xs text-slate-500">¿En qué sede deseas realizar tu pedido?</p>
                                </div>
                            </div>
                            @if($selectedBranchId)
                                <button type="button" wire:click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <div class="space-y-2.5 max-h-[60vh] overflow-y-auto pr-1">
                            @forelse($branches as $branch)
                                @php
                                    $isCurrent = $selectedBranchId === $branch->id;
                                @endphp
                                <button type="button"
                                    wire:click="selectBranch({{ $branch->id }})"
                                    class="w-full text-left p-3.5 rounded-xl border transition-all flex items-center justify-between group
                                        {{ $isCurrent ? 'border-[#ff7261] bg-gradient-to-r from-[#ff7261]/5 to-[#a855f7]/5 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/80' }}">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                                            {{ $isCurrent ? 'bg-gradient-to-br from-[#ff7261] to-[#a855f7] text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-semibold text-slate-900">{{ $branch->name }}</h4>
                                                @if($isCurrent)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#ff7261]/15 text-[#ff7261]">
                                                        Actual
                                                    </span>
                                                @endif
                                            </div>
                                            @if($branch->address)
                                                <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    <span class="truncate">{{ $branch->address }}{{ $branch->city ? ' - ' . $branch->city : '' }}</span>
                                                </p>
                                            @endif
                                            @if($branch->phone)
                                                <p class="text-[11px] text-slate-400 mt-0.5">Tel: {{ $branch->phone }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-2 flex-shrink-0">
                                        @if($isCurrent)
                                            <div class="w-6 h-6 rounded-full bg-[#ff7261] text-white flex items-center justify-center">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-6 h-6 rounded-full border-2 border-slate-300 group-hover:border-[#ff7261] transition-colors"></div>
                                        @endif
                                    </div>
                                </button>
                            @empty
                                <div class="text-center py-6 text-slate-500 text-sm">
                                    No hay sucursales disponibles en este momento.
                                </div>
                            @endforelse
                        </div>

                        @if(!$selectedBranchId)
                            <p class="text-[11px] text-slate-400 text-center mt-3">
                                Selecciona una sucursal para ver los productos disponibles.
                            </p>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    </div>
    @endif
</div>
