<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Tienda - MikPOS'); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>

<body class="antialiased bg-slate-50 font-sans min-h-screen flex flex-col">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!config('ecommerce.branch_id')): ?>
        
        <div class="min-h-screen flex items-center justify-center px-4">
            <div class="text-center max-w-md">
                <div class="w-16 h-16 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Tienda no disponible</h1>
                <p class="text-slate-500">La tienda no está disponible temporalmente. Por favor, intente más tarde.</p>
            </div>
        </div>
    <?php else: ?>
        
        <header class="bg-white border-b border-slate-200 sticky top-0 z-40" x-data="{ mobileMenuOpen: false }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    
                    <a href="<?php echo e(route('shop.catalog')); ?>" class="flex items-center gap-3 flex-shrink-0">
                        <div class="w-9 h-9 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-slate-900 hidden sm:block">MikPOS <span class="text-sm font-normal text-slate-500">Tienda</span></span>
                    </a>

                    
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="<?php echo e(route('shop.catalog')); ?>" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Catálogo</a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard('customer')->check()): ?>
                            <a href="<?php echo e(route('shop.orders')); ?>" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Mis Pedidos</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </nav>

                    
                    <div class="flex items-center gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard('customer')->check()): ?>
                            
                            <?php
                                $cartItems = session('ecommerce_cart.items', []);
                                $cartCount = count($cartItems);
                            ?>
                            <button class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                x-data="{ count: <?php echo e($cartCount); ?> }"
                                @cart-updated.window="count = $event.detail.count"
                                @click="$dispatch('toggle-cart-sidebar')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                                </svg>
                                <span x-show="count > 0" x-text="count > 99 ? '99+' : count"
                                    class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-xs font-bold rounded-full flex items-center justify-center">
                                </span>
                            </button>

                            
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition-colors">
                                    <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-semibold text-sm">
                                        <?php echo e(substr(Auth::guard('customer')->user()->first_name, 0, 1)); ?>

                                    </div>
                                    <span class="hidden sm:block text-sm font-medium text-slate-700">
                                        <?php echo e(Auth::guard('customer')->user()->first_name); ?>

                                    </span>
                                    <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition
                                    class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                                    <a href="<?php echo e(route('shop.orders')); ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        Mis Pedidos
                                    </a>
                                    <hr class="my-1 border-slate-100">
                                    <form method="POST" action="<?php echo e(route('shop.logout')); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Cerrar Sesión
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo e(route('shop.login')); ?>" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Iniciar Sesión</a>
                            <a href="<?php echo e(route('shop.register')); ?>" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">Registrarse</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                
                <div x-show="mobileMenuOpen" x-collapse class="md:hidden border-t border-slate-100 py-3 space-y-1">
                    <a href="<?php echo e(route('shop.catalog')); ?>" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg">Catálogo</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard('customer')->check()): ?>
                        <a href="<?php echo e(route('shop.orders')); ?>" class="block px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg">Mis Pedidos</a>
                        <button @click="$dispatch('toggle-cart-sidebar'); mobileMenuOpen = false" class="block w-full text-left px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg">Carrito</button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </header>

        
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1">
            <?php echo e($slot); ?>

        </main>

        
        <footer class="bg-white border-t border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-500">MikPOS Tienda</span>
                    </div>
                    <p class="text-sm text-slate-400">&copy; <?php echo e(date('Y')); ?> MikPOS. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if (isset($component)) { $__componentOriginal7cfab914afdd05940201ca0b2cbc009b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $attributes = $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $component = $__componentOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scriptConfig(); ?>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html>
<?php /**PATH C:\laragon\www\mikpos_v1.1\resources\views/layouts/shop.blade.php ENDPATH**/ ?>