<div>
    <div class="mb-6">
        <a href="<?php echo e(route('shop.cart')); ?>" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver al carrito
        </a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Checkout</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            
            <?php $customer = Auth::guard('customer')->user(); ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Datos del cliente</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-500">Nombre:</span>
                        <span class="ml-1 font-medium text-slate-900"><?php echo e($customer->full_name); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500">Documento:</span>
                        <span class="ml-1 font-medium text-slate-900"><?php echo e($customer->document_number); ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500">Email:</span>
                        <span class="ml-1 font-medium text-slate-900"><?php echo e($customer->email); ?></span>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->phone): ?>
                    <div>
                        <span class="text-slate-500">Teléfono:</span>
                        <span class="ml-1 font-medium text-slate-900"><?php echo e($customer->phone); ?></span>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Dirección de envío</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Departamento</label>
                            <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['wire:model.live' => 'department_id','options' => $departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->toArray(),'placeholder' => 'Seleccionar departamento...','searchPlaceholder' => 'Buscar departamento...']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model.live' => 'department_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->toArray()),'placeholder' => 'Seleccionar departamento...','searchPlaceholder' => 'Buscar departamento...']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $attributes = $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $component = $__componentOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['department_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Municipio</label>
                            <?php if (isset($component)) { $__componentOriginal93f56a9791a1857c74a51e0e80d6e731 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.searchable-select','data' => ['wire:model' => 'municipality_id','options' => $municipalities->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->toArray(),'placeholder' => 'Seleccionar municipio...','searchPlaceholder' => 'Buscar municipio...','disabled' => !$department_id]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('searchable-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model' => 'municipality_id','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($municipalities->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->toArray()),'placeholder' => 'Seleccionar municipio...','searchPlaceholder' => 'Buscar municipio...','disabled' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(!$department_id)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $attributes = $__attributesOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__attributesOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731)): ?>
<?php $component = $__componentOriginal93f56a9791a1857c74a51e0e80d6e731; ?>
<?php unset($__componentOriginal93f56a9791a1857c74a51e0e80d6e731); ?>
<?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['municipality_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                        <input type="text" wire:model="address" placeholder="Dirección de envío"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono de contacto</label>
                            <input type="text" wire:model="phone" placeholder="Teléfono"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                            <input type="text" wire:model="notes" placeholder="Notas adicionales (opcional)"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Método de pago</h2>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['payment_method_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mb-3"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $paymentMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label
                            class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all
                                <?php echo e($payment_method_id == $method->id ? 'border-[#ff7261] bg-[#ff7261]/5' : 'border-slate-200 hover:border-slate-300'); ?>"
                        >
                            <input type="radio" wire:model.live="payment_method_id" value="<?php echo e($method->id); ?>" class="sr-only">
                            <div class="w-8 h-8 rounded-lg <?php echo e($payment_method_id == $method->id ? 'bg-gradient-to-br from-[#ff7261] to-[#a855f7]' : 'bg-slate-100'); ?> flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 <?php echo e($payment_method_id == $method->id ? 'text-white' : 'text-slate-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium <?php echo e($payment_method_id == $method->id ? 'text-slate-900' : 'text-slate-600'); ?>"><?php echo e($method->name); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Resumen del pedido</h2>

                <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-center gap-3">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item['image']): ?>
                                <img src="<?php echo e(Storage::url($item['image'])); ?>" alt="<?php echo e($item['name']); ?>" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate"><?php echo e($item['name']); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($item['quantity']); ?> x $<?php echo e(number_format($item['unit_price'], 0, ',', '.')); ?></p>
                            </div>
                            <span class="text-sm font-medium text-slate-900">$<?php echo e(number_format($item['unit_price'] * $item['quantity'], 0, ',', '.')); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="border-t border-slate-200 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="text-slate-900">$<?php echo e(number_format($this->subtotal - $this->taxTotal, 0, ',', '.')); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Impuestos</span>
                        <span class="text-slate-900">$<?php echo e(number_format($this->taxTotal, 0, ',', '.')); ?></span>
                    </div>
                    <div class="flex justify-between text-base font-bold pt-2 border-t border-slate-200">
                        <span class="text-slate-900">Total</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff7261] to-[#a855f7]">$<?php echo e(number_format($this->total, 0, ',', '.')); ?></span>
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
<?php /**PATH C:\laragon\www\mikpos_v1.1\resources\views/livewire/shop/checkout.blade.php ENDPATH**/ ?>