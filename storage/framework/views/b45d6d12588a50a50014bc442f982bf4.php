<div class="space-y-6">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Productos</h1>
            <p class="text-slate-500 mt-1">Gestiona los productos y sus variantes</p>
        </div>
        <div class="flex items-center gap-2">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasPermission('products.view')): ?>
            <button wire:click="exportProducts" wire:loading.attr="disabled" wire:target="exportProducts" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span wire:loading.remove wire:target="exportProducts">Exportar CSV</span>
                <span wire:loading wire:target="exportProducts">Exportando...</span>
            </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(auth()->user()->hasPermission('products.delete')): ?>
            <button wire:click="openBulkDeleteModal" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 hover:bg-red-50 text-red-600 text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Eliminación Masiva
            </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(auth()->user()->hasPermission('products.create')): ?>
            <button wire:click="openImportModal" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Importar CSV
            </button>
            <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Producto
            </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col gap-4">
            
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por nombre, SKU o descripción...">
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select wire:model.live="filterCategory" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                        <option value="">Todas las categorías</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <select wire:model.live="filterBrand" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                        <option value="">Todas las marcas</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($brand->id); ?>"><?php echo e($brand->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <select wire:model.live="filterStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[120px]">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3 items-center">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($needsBranchSelection): ?>
                <select wire:model.live="filterBranch" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                    <option value="">Todas las sucursales</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <select wire:model.live="filterHasVariants" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Variantes: Todos</option>
                    <option value="1">Con variantes</option>
                    <option value="0">Sin variantes</option>
                </select>
                <select wire:model.live="filterStockStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Stock: Todos</option>
                    <option value="ok">Stock OK</option>
                    <option value="low">Stock bajo</option>
                    <option value="out">Sin stock</option>
                </select>
                
                <div class="flex items-center gap-2 ml-auto">
                    <span class="text-sm text-slate-500">Ordenar:</span>
                    <select wire:model.live="sortBy" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                        <option value="created_at">Fecha creación</option>
                        <option value="name">Nombre</option>
                        <option value="sale_price">Precio venta</option>
                        <option value="purchase_price">Precio compra</option>
                        <option value="current_stock">Stock</option>
                    </select>
                    <button wire:click="sortByColumn('<?php echo e($sortBy); ?>')" class="p-2.5 border border-slate-200 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors" title="<?php echo e($sortDirection === 'asc' ? 'Ascendente' : 'Descendente'); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortDirection === 'asc'): ?>
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                        <?php else: ?>
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </button>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search || $filterCategory || $filterBrand || ($filterStatus !== null && $filterStatus !== '') || $filterBranch || ($filterHasVariants !== null && $filterHasVariants !== '') || ($filterStockStatus !== null && $filterStockStatus !== '') || $sortBy !== 'created_at'): ?>
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Limpiar
                </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase w-10"></th>
                        <th wire:click="sortByColumn('name')" class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase cursor-pointer hover:text-slate-700 transition-colors">
                            <div class="flex items-center gap-1">
                                Producto
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'name'): ?>
                                <svg class="w-4 h-4 <?php echo e($sortDirection === 'asc' ? '' : 'rotate-180'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Categoría</th>
                        <th wire:click="sortByColumn('purchase_price')" class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase cursor-pointer hover:text-slate-700 transition-colors">
                            <div class="flex items-center justify-end gap-1">
                                P. Compra
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'purchase_price'): ?>
                                <svg class="w-4 h-4 <?php echo e($sortDirection === 'asc' ? '' : 'rotate-180'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th wire:click="sortByColumn('sale_price')" class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase cursor-pointer hover:text-slate-700 transition-colors">
                            <div class="flex items-center justify-end gap-1">
                                P. Venta
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'sale_price'): ?>
                                <svg class="w-4 h-4 <?php echo e($sortDirection === 'asc' ? '' : 'rotate-180'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th wire:click="sortByColumn('current_stock')" class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase cursor-pointer hover:text-slate-700 transition-colors">
                            <div class="flex items-center justify-center gap-1">
                                Stock
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sortBy === 'current_stock'): ?>
                                <svg class="w-4 h-4 <?php echo e($sortDirection === 'asc' ? '' : 'rotate-180'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Variantes</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50/50 transition-colors <?php echo e(in_array($item->id, $expandedProducts) ? 'bg-slate-50' : ''); ?>">
                        <td class="px-6 py-4">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->children_count > 0): ?>
                            <button wire:click="toggleExpand(<?php echo e($item->id); ?>)" class="p-1 text-slate-400 hover:text-slate-600 rounded transition-colors">
                                <svg class="w-5 h-5 transform transition-transform <?php echo e(in_array($item->id, $expandedProducts) ? 'rotate-90' : ''); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->image): ?>
                                <img src="<?php echo e(Storage::url($item->image)); ?>" alt="<?php echo e($item->name); ?>" class="w-10 h-10 rounded-lg object-cover">
                                <?php else: ?>
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div>
                                    <div class="font-medium text-slate-900"><?php echo e($item->name); ?></div>
                                    <div class="text-sm text-slate-500">SKU: <?php echo e($item->sku ?? 'Sin SKU'); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->category): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-purple-100 text-purple-700"><?php echo e($item->category->name); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->subcategory): ?>
                            <div class="text-xs text-slate-500 mt-1"><?php echo e($item->subcategory->name); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-slate-600">$<?php echo e(number_format($item->purchase_price, 0, ',', '.')); ?></span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm">
                                <span class="text-slate-900 font-medium">$<?php echo e(number_format($item->sale_price, 0, ',', '.')); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->hasNegativeMargin()): ?>
                                <span class="ml-1 text-red-500" title="Margen negativo">⚠️</span>
                                <?php else: ?>
                                <?php $margin = $item->getMargin(); ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($margin !== null): ?>
                                <span class="ml-1 text-xs <?php echo e($margin >= 20 ? 'text-green-600' : ($margin >= 10 ? 'text-amber-600' : 'text-red-500')); ?>">
                                    (<?php echo e(number_format($margin, 1)); ?>%)
                                </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->brand): ?>
                            <div class="text-xs text-slate-500"><?php echo e($item->brand->name); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm <?php echo e($item->isLowStock() ? 'text-amber-600 font-medium' : 'text-slate-600'); ?>">
                                <?php echo e(rtrim(rtrim(number_format($item->current_stock, 3), '0'), '.')); ?> <?php echo e($item->unit?->abbreviation ?? 'und'); ?>

                            </span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->isLowStock()): ?>
                            <div class="text-xs text-amber-500">Stock bajo</div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->children_count > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                                <?php echo e($item->children_count); ?> <?php echo e($item->children_count === 1 ? 'variante' : 'variantes'); ?>

                            </span>
                            <?php else: ?>
                            <span class="text-slate-400 text-sm">Sin variantes</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if(auth()->user()->hasPermission('products.edit')): ?>
                            <button wire:click="toggleStatus(<?php echo e($item->id); ?>)" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 <?php echo e($item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200'); ?>">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 <?php echo e($item->is_active ? 'translate-x-5' : 'translate-x-0'); ?>"></span>
                            </button>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium <?php echo e($item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                                <?php echo e($item->is_active ? 'Activo' : 'Inactivo'); ?>

                            </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <?php if(auth()->user()->hasPermission('products.create')): ?>
                                <button wire:click="createChild(<?php echo e($item->id); ?>)" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Agregar variante">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(auth()->user()->hasPermission('products.edit')): ?>
                                <button wire:click="manageBarcodes(<?php echo e($item->id); ?>)" class="p-2 text-slate-400 hover:text-purple-500 hover:bg-purple-50 rounded-lg transition-colors" title="Códigos de barras">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                </button>
                                <button wire:click="edit(<?php echo e($item->id); ?>)" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(auth()->user()->hasPermission('products.delete')): ?>
                                <button wire:click="confirmDelete(<?php echo e($item->id); ?>)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($item->id, $expandedProducts) && $item->children_count > 0): ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $item->children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="bg-slate-50/70 hover:bg-slate-100/50 transition-colors">
                        <td class="px-6 py-3"></td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3 pl-6">
                                <div class="w-1 h-8 bg-slate-300 rounded-full"></div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($child->image || $item->image): ?>
                                <img src="<?php echo e(Storage::url($child->getDisplayImage())); ?>" alt="<?php echo e($child->name); ?>" class="w-8 h-8 rounded-lg object-cover">
                                <?php else: ?>
                                <div class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div>
                                    <div class="font-medium text-slate-700 text-sm"><?php echo e($child->name); ?></div>
                                    <div class="text-xs text-slate-500">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($child->sku): ?>SKU: <?php echo e($child->sku); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($child->barcode): ?> · <?php echo e($child->barcode); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($child->presentation): ?>
                            <span class="text-sm text-slate-600"><?php echo e($child->presentation->name); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($child->color): ?>
                            <div class="flex items-center gap-1 mt-1">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($child->color->hex_code): ?>
                                <span class="w-3 h-3 rounded-full border border-slate-300" style="background-color: <?php echo e($child->color->hex_code); ?>"></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <span class="text-xs text-slate-500"><?php echo e($child->color->name); ?></span>
                            </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <span class="text-xs text-slate-400">(padre)</span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="text-sm">
                                <span class="text-slate-600">$<?php echo e(number_format($child->sale_price, 0, ',', '.')); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($child->hasNegativeMargin()): ?>
                                <span class="ml-1 text-red-500" title="Margen negativo">⚠️</span>
                                <?php else: ?>
                                <?php $margin = $child->getMargin(); ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($margin !== null): ?>
                                <span class="ml-1 text-xs <?php echo e($margin >= 20 ? 'text-green-600' : ($margin >= 10 ? 'text-amber-600' : 'text-red-500')); ?>">
                                    (<?php echo e(number_format($margin, 1)); ?>%)
                                </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="text-sm text-slate-500" title="Stock del producto padre">
                                <?php echo e(rtrim(rtrim(number_format($item->current_stock, 3), '0'), '.')); ?> <?php echo e($item->unit?->abbreviation ?? 'und'); ?>

                            </span>
                            <div class="text-xs text-slate-400">(padre)</div>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="text-xs text-slate-400">x<?php echo e($child->unit_quantity); ?></span>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <?php if(auth()->user()->hasPermission('products.edit')): ?>
                            <button wire:click="toggleChildStatus(<?php echo e($child->id); ?>)" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 <?php echo e($child->is_active ? 'bg-[#ff7261]' : 'bg-slate-200'); ?>">
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 <?php echo e($child->is_active ? 'translate-x-4' : 'translate-x-0'); ?>"></span>
                            </button>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo e($child->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                                <?php echo e($child->is_active ? 'Activo' : 'Inactivo'); ?>

                            </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <?php if(auth()->user()->hasPermission('products.edit')): ?>
                                <button wire:click="manageChildBarcodes(<?php echo e($child->id); ?>)" class="p-1.5 text-slate-400 hover:text-purple-500 hover:bg-purple-50 rounded-lg transition-colors" title="Códigos de barras">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                </button>
                                <button wire:click="editChild(<?php echo e($child->id); ?>)" class="p-1.5 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar variante">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(auth()->user()->hasPermission('products.delete')): ?>
                                <button wire:click="confirmDeleteChild(<?php echo e($child->id); ?>)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar variante">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <p>No hay productos registrados</p>
                                <?php if($search || $filterCategory || $filterBrand || ($filterStatus !== null && $filterStatus !== '') || ($filterHasVariants !== null && $filterHasVariants !== '') || ($filterStockStatus !== null && $filterStockStatus !== '')): ?>
                                <button wire:click="clearFilters" class="mt-2 text-[#ff7261] hover:underline text-sm">Limpiar filtros</button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($items->hasPages()): ?>
        <div class="px-6 py-4 border-t border-slate-200">
            <?php echo e($items->links()); ?>

        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isModalOpen): ?>
    <div class="relative z-[100]" x-data="{
        purchasePrice: <?php if ((object) ('purchase_price') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('purchase_price'->value()); ?>')<?php echo e('purchase_price'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('purchase_price'); ?>')<?php endif; ?>,
        salePrice: <?php if ((object) ('sale_price') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('sale_price'->value()); ?>')<?php echo e('sale_price'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('sale_price'); ?>')<?php endif; ?>,
        get margin() {
            if (!this.purchasePrice || this.purchasePrice <= 0) return null;
            return ((this.salePrice - this.purchasePrice) / this.purchasePrice * 100).toFixed(1);
        },
        get hasNegativeMargin() {
            return this.salePrice < this.purchasePrice;
        }
    }">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900"><?php echo e($itemId ? 'Editar' : 'Nuevo'); ?> Producto</h3>
                    </div>
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($needsBranchSelection): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-amber-800 mb-1">Selección de Sucursal Requerida</h4>
                                    <p class="text-sm text-amber-700 mb-3">Como administrador general, debes seleccionar la sucursal a la que pertenecerá este producto.</p>
                                    <select wire:model="branch_id" class="w-full px-3 py-2 border border-amber-300 rounded-xl bg-white focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500">
                                        <option value="">Seleccionar sucursal...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($branch->id); ?>"><?php echo e($branch->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['branch_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-sm mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Información Básica
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">SKU <span class="text-slate-400 font-normal">(se genera automáticamente)</span></label>
                                    <input wire:model="sku" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: MED-00001">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['sku'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                                    <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Acetaminofén">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                                <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción del producto"></textarea>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                Clasificación
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Categoría *</label>
                                    <select wire:model.live="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar categoría...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Subcategoría</label>
                                    <select wire:model="subcategory_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" <?php echo e(empty($subcategories) ? 'disabled' : ''); ?>>
                                        <option value="">Seleccionar subcategoría...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $subcategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subcat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($subcat->id); ?>"><?php echo e($subcat->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Marca</label>
                                    <select wire:model="brand_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar marca...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($brand->id); ?>"><?php echo e($brand->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Unidad Base *</label>
                                    <select wire:model="unit_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar unidad...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($unit->id); ?>"><?php echo e($unit->name); ?> (<?php echo e($unit->abbreviation); ?>)</option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['unit_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                            $hasVisibleFields = false;
                            foreach (['barcode', 'presentation_id', 'color_id', 'product_model_id', 'size', 'weight', 'imei'] as $fn) {
                                $f = $fieldSettings[$fn] ?? null;
                                if ($f && (is_object($f) ? $f->parent_visible : ($f['parent_visible'] ?? false))) {
                                    $hasVisibleFields = true;
                                    break;
                                }
                            }
                        ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasVisibleFields): ?>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                Atributos Adicionales
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php
                                    $barcodeField = $fieldSettings['barcode'] ?? null;
                                    $barcodeVisible = $barcodeField ? (is_object($barcodeField) ? $barcodeField->parent_visible : ($barcodeField['parent_visible'] ?? false)) : false;
                                    $barcodeRequired = $barcodeField ? (is_object($barcodeField) ? $barcodeField->parent_required : ($barcodeField['parent_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($barcodeVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Código de Barras <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($barcodeRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="barcode" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 7701234567890">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['barcode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $presentationField = $fieldSettings['presentation_id'] ?? null;
                                    $presentationVisible = $presentationField ? (is_object($presentationField) ? $presentationField->parent_visible : ($presentationField['parent_visible'] ?? false)) : false;
                                    $presentationRequired = $presentationField ? (is_object($presentationField) ? $presentationField->parent_required : ($presentationField['parent_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($presentationVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Presentación <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($presentationRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <select wire:model="presentation_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar presentación...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $presentations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $presentation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($presentation->id); ?>"><?php echo e($presentation->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['presentation_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $colorField = $fieldSettings['color_id'] ?? null;
                                    $colorVisible = $colorField ? (is_object($colorField) ? $colorField->parent_visible : ($colorField['parent_visible'] ?? false)) : false;
                                    $colorRequired = $colorField ? (is_object($colorField) ? $colorField->parent_required : ($colorField['parent_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($colorVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Color <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($colorRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <select wire:model="color_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar color...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($color->id); ?>"><?php echo e($color->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['color_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $modelField = $fieldSettings['product_model_id'] ?? null;
                                    $modelVisible = $modelField ? (is_object($modelField) ? $modelField->parent_visible : ($modelField['parent_visible'] ?? false)) : false;
                                    $modelRequired = $modelField ? (is_object($modelField) ? $modelField->parent_required : ($modelField['parent_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modelVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Modelo <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modelRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <select wire:model="product_model_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar modelo...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $productModels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $model): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($model->id); ?>"><?php echo e($model->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['product_model_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $sizeField = $fieldSettings['size'] ?? null;
                                    $sizeVisible = $sizeField ? (is_object($sizeField) ? $sizeField->parent_visible : ($sizeField['parent_visible'] ?? false)) : false;
                                    $sizeRequired = $sizeField ? (is_object($sizeField) ? $sizeField->parent_required : ($sizeField['parent_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sizeVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Talla <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sizeRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="size" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: M, L, XL">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['size'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $weightField = $fieldSettings['weight'] ?? null;
                                    $weightVisible = $weightField ? (is_object($weightField) ? $weightField->parent_visible : ($weightField['parent_visible'] ?? false)) : false;
                                    $weightRequired = $weightField ? (is_object($weightField) ? $weightField->parent_required : ($weightField['parent_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($weightVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Peso (g) <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($weightRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="weight" type="number" step="0.001" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 100.5">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['weight'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $imeiField = $fieldSettings['imei'] ?? null;
                                    $imeiVisible = $imeiField ? (is_object($imeiField) ? $imeiField->parent_visible : ($imeiField['parent_visible'] ?? false)) : false;
                                    $imeiRequired = $imeiField ? (is_object($imeiField) ? $imeiField->parent_required : ($imeiField['parent_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imeiVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">IMEI <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imeiRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="imei" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="15-17 dígitos">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['imei'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Precios
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio Compra *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                                        <input wire:model.live="purchase_price" x-model.number="purchasePrice" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['purchase_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio Venta *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                                        <input wire:model.live="sale_price" x-model.number="salePrice" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['sale_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio Especial</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-green-600">$</span>
                                        <input wire:model="special_price" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-2 border border-green-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500 bg-green-50" placeholder="0.00">
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Precio promocional o descuento</p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model="price_includes_tax" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Precio incluye impuesto</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Margen:</span>
                                    <template x-if="margin !== null">
                                        <span class="text-sm font-semibold" :class="{
                                            'text-green-600': margin >= 20,
                                            'text-amber-600': margin >= 10 && margin < 20,
                                            'text-red-500': margin < 10
                                        }" x-text="margin + '%'"></span>
                                    </template>
                                    <template x-if="margin === null">
                                        <span class="text-sm text-slate-400">-</span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Ganancia:</span>
                                    <template x-if="purchasePrice > 0">
                                        <span class="text-sm font-semibold" :class="{
                                            'text-green-600': (salePrice - purchasePrice) > 0,
                                            'text-red-500': (salePrice - purchasePrice) <= 0
                                        }" x-text="'$' + (salePrice - purchasePrice).toFixed(2)"></span>
                                    </template>
                                    <template x-if="!purchasePrice || purchasePrice <= 0">
                                        <span class="text-sm text-slate-400">-</span>
                                    </template>
                                </div>
                            </div>
                            <div x-show="hasNegativeMargin" x-transition class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="text-sm text-red-700">Advertencia: El precio de venta es menor al precio de compra</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Inventario
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Inicial *</label>
                                    <input wire:model="current_stock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['current_stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Mínimo</label>
                                    <input wire:model="min_stock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['min_stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Máximo</label>
                                    <input wire:model="max_stock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Opcional">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['max_stock'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                                Impuesto
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Impuesto</label>
                                    <select wire:model="tax_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Sin impuesto</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $taxes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tax): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($tax->id); ?>"><?php echo e($tax->name); ?> (<?php echo e($tax->rate); ?>%)</option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div x-data="{
                            hasCommission: <?php if ((object) ('has_commission') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('has_commission'->value()); ?>')<?php echo e('has_commission'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('has_commission'); ?>')<?php endif; ?>,
                            commissionType: <?php if ((object) ('commission_type') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('commission_type'->value()); ?>')<?php echo e('commission_type'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('commission_type'); ?>')<?php endif; ?>,
                            commissionValue: <?php if ((object) ('commission_value') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('commission_value'->value()); ?>')<?php echo e('commission_value'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('commission_value'); ?>')<?php endif; ?>,
                            get commissionAmount() {
                                if (!this.hasCommission || !this.commissionValue) return 0;
                                if (this.commissionType === 'percentage') {
                                    return (salePrice * (this.commissionValue / 100)).toFixed(2);
                                }
                                return parseFloat(this.commissionValue).toFixed(2);
                            },
                            get profitAfterCommission() {
                                const profit = salePrice - purchasePrice;
                                return (profit - parseFloat(this.commissionAmount)).toFixed(2);
                            }
                        }">
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Comisión Vendedor
                            </h4>
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input wire:model.live="has_commission" x-model="hasCommission" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Este producto tiene comisión para el vendedor</span>
                                </label>
                                
                                <div x-show="hasCommission" x-transition class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-100 space-y-4">
                                    <div class="flex gap-3">
                                        <button type="button" @click="commissionType = 'percentage'; $wire.set('commission_type', 'percentage')" 
                                            :class="commissionType === 'percentage' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2.5 px-4 rounded-xl font-medium transition-all text-sm">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            Porcentaje
                                        </button>
                                        <button type="button" @click="commissionType = 'fixed'; $wire.set('commission_type', 'fixed')"
                                            :class="commissionType === 'fixed' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2.5 px-4 rounded-xl font-medium transition-all text-sm">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Valor Fijo
                                        </button>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1" x-text="commissionType === 'percentage' ? 'Porcentaje de comisión' : 'Valor de comisión'"></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500" x-text="commissionType === 'percentage' ? '%' : '$'"></span>
                                            <input wire:model.live="commission_value" x-model.number="commissionValue" type="number" step="0.01" min="0" :max="commissionType === 'percentage' ? 100 : undefined" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 pt-2">
                                        <div class="p-3 bg-white rounded-xl border border-purple-200">
                                            <p class="text-xs text-slate-500 mb-1">Comisión por venta</p>
                                            <p class="text-lg font-bold text-purple-600">$<span x-text="commissionAmount">0.00</span></p>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-green-200">
                                            <p class="text-xs text-slate-500 mb-1">Ganancia neta</p>
                                            <p class="text-lg font-bold" :class="profitAfterCommission >= 0 ? 'text-green-600' : 'text-red-500'">$<span x-text="profitAfterCommission">0.00</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Producto activo</span>
                            </label>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Imagen del Producto
                            </h4>
                            <div class="space-y-3">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($existingImage || $image): ?>
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($image): ?>
                                        <img src="<?php echo e($image->temporaryUrl()); ?>" alt="Preview" class="w-24 h-24 rounded-xl object-cover border border-slate-200">
                                        <?php elseif($existingImage): ?>
                                        <img src="<?php echo e(Storage::url($existingImage)); ?>" alt="Imagen actual" class="w-24 h-24 rounded-xl object-cover border border-slate-200">
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <button type="button" wire:click="removeImage" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($image): ?>
                                        <p>Nueva imagen seleccionada</p>
                                        <?php else: ?>
                                        <p>Imagen actual</p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-8 h-8 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <p class="mb-1 text-sm text-slate-500"><span class="font-semibold">Clic para subir</span> o arrastra y suelta</p>
                                            <p class="text-xs text-slate-400">JPG, PNG o WebP (máx. 2MB)</p>
                                        </div>
                                        <input wire:model="image" type="file" class="hidden" accept="image/jpeg,image/png,image/webp">
                                    </label>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div wire:loading wire:target="image" class="text-sm text-slate-500 flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-[#ff7261]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Subiendo imagen...
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            <span wire:loading.remove wire:target="store">Guardar</span>
                            <span wire:loading wire:target="store">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isChildModalOpen): ?>
    <div class="relative z-[100]" x-data="{
        unitQuantity: <?php if ((object) ('childUnitQuantity') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childUnitQuantity'->value()); ?>')<?php echo e('childUnitQuantity'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childUnitQuantity'); ?>')<?php endif; ?>,
        parentPurchasePrice: <?php echo e($parentProduct?->purchase_price ?? 0); ?>,
        salePrice: <?php if ((object) ('childSalePrice') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childSalePrice'->value()); ?>')<?php echo e('childSalePrice'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childSalePrice'); ?>')<?php endif; ?>,
        get purchasePrice() {
            return this.parentPurchasePrice * this.unitQuantity;
        },
        get margin() {
            if (!this.purchasePrice || this.purchasePrice <= 0) return null;
            return ((this.salePrice - this.purchasePrice) / this.purchasePrice * 100).toFixed(1);
        },
        get profit() {
            return this.salePrice - this.purchasePrice;
        },
        get hasNegativeMargin() {
            return this.profit < 0;
        }
    }">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isChildModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">
                            <?php echo e($childId ? 'Editar' : 'Nueva'); ?> Variante
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($parentProduct): ?>
                            <span class="text-slate-500 font-normal">- <?php echo e($parentProduct->name); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </h3>
                    </div>
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($parentProduct): ?>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Información Heredada del Producto Padre
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                                <div>
                                    <span class="text-slate-500">Categoría:</span>
                                    <p class="font-medium text-slate-700"><?php echo e($parentProduct->category?->name ?? '-'); ?></p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Marca:</span>
                                    <p class="font-medium text-slate-700"><?php echo e($parentProduct->brand?->name ?? '-'); ?></p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Impuesto:</span>
                                    <p class="font-medium text-slate-700"><?php echo e($parentProduct->tax ? $parentProduct->tax->name . ' (' . $parentProduct->tax->rate . '%)' : 'Sin impuesto'); ?></p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Unidad Base:</span>
                                    <p class="font-medium text-slate-700"><?php echo e($parentProduct->unit?->name ?? '-'); ?> (<?php echo e($parentProduct->unit?->abbreviation ?? '-'); ?>)</p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Precio Compra (por unidad):</span>
                                    <p class="font-medium text-slate-700">$<?php echo e(number_format($parentProduct->purchase_price, 2)); ?></p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Stock Actual:</span>
                                    <p class="font-medium text-slate-700"><?php echo e(rtrim(rtrim(number_format($parentProduct->current_stock, 3), '0'), '.')); ?> <?php echo e($parentProduct->unit?->abbreviation ?? 'und'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Información de Variante
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre Variante *</label>
                                    <input wire:model="childName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Caja x100">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">SKU <span class="text-slate-400 font-normal">(opcional)</span></label>
                                    <input wire:model="childSku" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: MED-001-100">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childSku'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">
                                        Cantidad de <?php echo e($parentProduct?->unit?->name ?? 'unidades'); ?> por variante *
                                        <span class="text-slate-400 font-normal">(cuántas <?php echo e($parentProduct?->unit?->abbreviation ?? 'und'); ?> del padre consume esta variante)</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input wire:model.live="childUnitQuantity" x-model.number="unitQuantity" type="number" step="0.001" min="0.001" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 6">
                                        <span class="text-sm text-slate-500 whitespace-nowrap"><?php echo e($parentProduct?->unit?->abbreviation ?? 'und'); ?></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Ej: Si el padre es "Pastilla" y la variante es "Tableta x6", ingresa 6</p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childUnitQuantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                Atributos
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php
                                    $barcodeField = $fieldSettings['barcode'] ?? null;
                                    $barcodeVisible = $barcodeField ? (is_object($barcodeField) ? $barcodeField->child_visible : ($barcodeField['child_visible'] ?? false)) : false;
                                    $barcodeRequired = $barcodeField ? (is_object($barcodeField) ? $barcodeField->child_required : ($barcodeField['child_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($barcodeVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Código de Barras <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($barcodeRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="childBarcode" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 7701234567890">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childBarcode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $presentationField = $fieldSettings['presentation_id'] ?? null;
                                    $presentationVisible = $presentationField ? (is_object($presentationField) ? $presentationField->child_visible : ($presentationField['child_visible'] ?? false)) : false;
                                    $presentationRequired = $presentationField ? (is_object($presentationField) ? $presentationField->child_required : ($presentationField['child_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($presentationVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Presentación <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($presentationRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <select wire:model="childPresentationId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar presentación...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $presentations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $presentation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($presentation->id); ?>"><?php echo e($presentation->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childPresentationId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $colorField = $fieldSettings['color_id'] ?? null;
                                    $colorVisible = $colorField ? (is_object($colorField) ? $colorField->child_visible : ($colorField['child_visible'] ?? false)) : false;
                                    $colorRequired = $colorField ? (is_object($colorField) ? $colorField->child_required : ($colorField['child_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($colorVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Color <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($colorRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <select wire:model="childColorId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar color...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($color->id); ?>"><?php echo e($color->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childColorId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $modelField = $fieldSettings['product_model_id'] ?? null;
                                    $modelVisible = $modelField ? (is_object($modelField) ? $modelField->child_visible : ($modelField['child_visible'] ?? false)) : false;
                                    $modelRequired = $modelField ? (is_object($modelField) ? $modelField->child_required : ($modelField['child_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modelVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Modelo <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($modelRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <select wire:model="childProductModelId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar modelo...</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $productModels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $model): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($model->id); ?>"><?php echo e($model->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childProductModelId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $sizeField = $fieldSettings['size'] ?? null;
                                    $sizeVisible = $sizeField ? (is_object($sizeField) ? $sizeField->child_visible : ($sizeField['child_visible'] ?? false)) : false;
                                    $sizeRequired = $sizeField ? (is_object($sizeField) ? $sizeField->child_required : ($sizeField['child_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sizeVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Talla <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sizeRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="childSize" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: M, L, XL">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childSize'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $weightField = $fieldSettings['weight'] ?? null;
                                    $weightVisible = $weightField ? (is_object($weightField) ? $weightField->child_visible : ($weightField['child_visible'] ?? false)) : false;
                                    $weightRequired = $weightField ? (is_object($weightField) ? $weightField->child_required : ($weightField['child_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($weightVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Peso (g) <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($weightRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="childWeight" type="number" step="0.001" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 100.5">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childWeight'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                <?php
                                    $imeiField = $fieldSettings['imei'] ?? null;
                                    $imeiVisible = $imeiField ? (is_object($imeiField) ? $imeiField->child_visible : ($imeiField['child_visible'] ?? false)) : false;
                                    $imeiRequired = $imeiField ? (is_object($imeiField) ? $imeiField->child_required : ($imeiField['child_required'] ?? false)) : false;
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imeiVisible): ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">IMEI <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($imeiRequired): ?>*<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></label>
                                    <input wire:model="childImei" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="15-17 dígitos">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childImei'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Precios
                            </h4>
                            
                            <div class="bg-blue-50 rounded-xl p-3 mb-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-blue-700">Costo calculado (<?php echo e($parentProduct?->unit?->abbreviation ?? 'und'); ?> x cantidad):</span>
                                    <span class="text-lg font-bold text-blue-800" x-text="'$' + purchasePrice.toFixed(2)"></span>
                                </div>
                                <p class="text-xs text-blue-600 mt-1">= $<?php echo e(number_format($parentProduct?->purchase_price ?? 0, 2)); ?> × <span x-text="unitQuantity"></span> <?php echo e($parentProduct?->unit?->abbreviation ?? 'und'); ?></p>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio Venta *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                                        <input wire:model.live="childSalePrice" x-model.number="salePrice" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childSalePrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model="childPriceIncludesTax" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Precio incluye impuesto</span>
                                </label>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Margen:</span>
                                    <template x-if="margin !== null">
                                        <span class="text-sm font-semibold" :class="{
                                            'text-green-600': margin >= 20,
                                            'text-amber-600': margin >= 10 && margin < 20,
                                            'text-red-500': margin < 10
                                        }" x-text="margin + '%'"></span>
                                    </template>
                                    <template x-if="margin === null">
                                        <span class="text-sm text-slate-400">-</span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Ganancia:</span>
                                    <span class="text-sm font-semibold" :class="{
                                        'text-green-600': profit > 0,
                                        'text-red-500': profit <= 0
                                    }" x-text="'$' + profit.toFixed(2)"></span>
                                </div>
                            </div>
                            
                            <div x-show="hasNegativeMargin" x-transition class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="text-sm text-red-700">Advertencia: El precio de venta es menor al costo calculado</span>
                            </div>
                        </div>

                        
                        <div x-data="{
                            hasCommission: <?php if ((object) ('childHasCommission') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childHasCommission'->value()); ?>')<?php echo e('childHasCommission'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childHasCommission'); ?>')<?php endif; ?>,
                            commissionType: <?php if ((object) ('childCommissionType') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childCommissionType'->value()); ?>')<?php echo e('childCommissionType'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childCommissionType'); ?>')<?php endif; ?>,
                            commissionValue: <?php if ((object) ('childCommissionValue') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childCommissionValue'->value()); ?>')<?php echo e('childCommissionValue'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('childCommissionValue'); ?>')<?php endif; ?>,
                            get commissionAmount() {
                                if (!this.hasCommission || !this.commissionValue) return 0;
                                if (this.commissionType === 'percentage') {
                                    return (salePrice * (this.commissionValue / 100)).toFixed(2);
                                }
                                return parseFloat(this.commissionValue).toFixed(2);
                            },
                            get profitAfterCommission() {
                                return (profit - parseFloat(this.commissionAmount)).toFixed(2);
                            }
                        }">
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Comisión Vendedor
                            </h4>
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input wire:model.live="childHasCommission" x-model="hasCommission" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Esta variante tiene comisión para el vendedor</span>
                                </label>
                                
                                <div x-show="hasCommission" x-transition class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-100 space-y-4">
                                    <div class="flex gap-3">
                                        <button type="button" @click="commissionType = 'percentage'; $wire.set('childCommissionType', 'percentage')" 
                                            :class="commissionType === 'percentage' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2 px-3 rounded-xl font-medium transition-all text-sm">
                                            Porcentaje
                                        </button>
                                        <button type="button" @click="commissionType = 'fixed'; $wire.set('childCommissionType', 'fixed')"
                                            :class="commissionType === 'fixed' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2 px-3 rounded-xl font-medium transition-all text-sm">
                                            Valor Fijo
                                        </button>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1" x-text="commissionType === 'percentage' ? 'Porcentaje de comisión' : 'Valor de comisión'"></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500" x-text="commissionType === 'percentage' ? '%' : '$'"></span>
                                            <input wire:model.live="childCommissionValue" x-model.number="commissionValue" type="number" step="0.01" min="0" :max="commissionType === 'percentage' ? 100 : undefined" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="p-3 bg-white rounded-xl border border-purple-200">
                                            <p class="text-xs text-slate-500 mb-1">Comisión por venta</p>
                                            <p class="text-lg font-bold text-purple-600">$<span x-text="commissionAmount">0.00</span></p>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-green-200">
                                            <p class="text-xs text-slate-500 mb-1">Ganancia neta</p>
                                            <p class="text-lg font-bold" :class="profitAfterCommission >= 0 ? 'text-green-600' : 'text-red-500'">$<span x-text="profitAfterCommission">0.00</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="childIsActive" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Variante activa</span>
                            </label>
                        </div>

                        
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Imagen de la Variante
                                <span class="text-slate-400 font-normal text-xs">(opcional, usa imagen del padre si no se especifica)</span>
                            </h4>
                            <div class="space-y-3">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($childExistingImage || $childImage): ?>
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($childImage): ?>
                                        <img src="<?php echo e($childImage->temporaryUrl()); ?>" alt="Preview" class="w-20 h-20 rounded-xl object-cover border border-slate-200">
                                        <?php elseif($childExistingImage): ?>
                                        <img src="<?php echo e(Storage::url($childExistingImage)); ?>" alt="Imagen actual" class="w-20 h-20 rounded-xl object-cover border border-slate-200">
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <button type="button" wire:click="removeChildImage" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($childImage): ?>
                                        <p>Nueva imagen seleccionada</p>
                                        <?php else: ?>
                                        <p>Imagen actual</p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                        <div class="flex flex-col items-center justify-center pt-4 pb-5">
                                            <svg class="w-6 h-6 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <p class="mb-1 text-sm text-slate-500"><span class="font-semibold">Clic para subir</span></p>
                                            <p class="text-xs text-slate-400">JPG, PNG o WebP (máx. 2MB)</p>
                                        </div>
                                        <input wire:model="childImage" type="file" class="hidden" accept="image/jpeg,image/png,image/webp">
                                    </label>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['childImage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="text-red-500 text-sm"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div wire:loading wire:target="childImage" class="text-sm text-slate-500 flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-[#ff7261]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Subiendo imagen...
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isChildModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="storeChild" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            <span wire:loading.remove wire:target="storeChild">Guardar</span>
                            <span wire:loading wire:target="storeChild">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isDeleteModalOpen): ?>
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Producto</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro? Esta acción eliminará el producto y todas sus variantes. No se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">
                            <span wire:loading.remove wire:target="delete">Eliminar</span>
                            <span wire:loading wire:target="delete">Eliminando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isChildDeleteModalOpen): ?>
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isChildDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Variante</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de eliminar esta variante? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isChildDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="deleteChild" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">
                            <span wire:loading.remove wire:target="deleteChild">Eliminar</span>
                            <span wire:loading wire:target="deleteChild">Eliminando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isImportModalOpen): ?>
    <div class="relative z-[100]" x-data="{ dragover: false }">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeImportModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Importar Productos desde CSV</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Carga masiva de productos y variantes</p>
                        </div>
                        <button wire:click="closeImportModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-blue-800 mb-2">Pasos para Importar</h4>
                                    <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                                        <li>Descarga la plantilla Excel (.xlsx)</li>
                                        <li>Abre el archivo y completa los datos (ver hoja "Instrucciones")</li>
                                        <li>Elimina las filas de ejemplo</li>
                                        <li><span class="font-semibold">Guarda como CSV:</span> Archivo → Guardar como → CSV UTF-8</li>
                                        <li>Sube el archivo CSV aquí</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        
                        <div class="flex justify-center">
                            <button wire:click="downloadTemplate" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Descargar Plantilla Excel
                            </button>
                        </div>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($needsBranchSelection && !$filterBranch): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div>
                                    <h4 class="text-sm font-semibold text-amber-800">Selecciona una Sucursal</h4>
                                    <p class="text-sm text-amber-700 mt-1">Debes seleccionar una sucursal en los filtros antes de importar productos.</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <div 
                            class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors"
                            :class="dragover ? 'border-[#ff7261] bg-orange-50' : 'border-slate-300 hover:border-slate-400'"
                            x-on:dragover.prevent="dragover = true"
                            x-on:dragleave.prevent="dragover = false"
                            x-on:drop.prevent="dragover = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                        >
                            <input 
                                type="file" 
                                wire:model="importFile" 
                                accept=".csv"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                x-ref="fileInput"
                            >
                            
                            <div class="pointer-events-none" wire:loading.remove wire:target="importFile">
                                <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                <p class="text-slate-600 font-medium">Arrastra tu archivo CSV aquí</p>
                                <p class="text-slate-400 text-sm mt-1">Solo archivos .csv (guardado desde Excel)</p>
                            </div>
                            
                            <div wire:loading wire:target="importFile" class="pointer-events-none">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-[#ff7261] animate-spin mb-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-slate-700 font-semibold">Procesando archivo...</p>
                                    <p class="text-slate-500 text-sm mt-1">Por favor espere</p>
                                </div>
                            </div>
                        </div>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($importErrors) > 0): ?>
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-red-800 mb-2">Errores encontrados</h4>
                                    <ul class="text-sm text-red-700 space-y-1 max-h-32 overflow-y-auto">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $importErrors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($error['row'] > 0): ?>
                                            <span class="font-medium">Fila <?php echo e($error['row']); ?>:</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php echo e($error['message']); ?>

                                        </li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($importPreview) > 0): ?>
                        <div>
                            <?php
                                $validCount = count(array_filter($importPreview, fn($r) => $r['valid']));
                                $invalidCount = count(array_filter($importPreview, fn($r) => !$r['valid']));
                                $warningCount = count(array_filter($importPreview, fn($r) => $r['hasWarnings'] ?? false));
                                
                                // Apply filter
                                if ($importFilter === 'errors') {
                                    $filteredPreview = array_filter($importPreview, fn($r) => !$r['valid']);
                                } elseif ($importFilter === 'warnings') {
                                    $filteredPreview = array_filter($importPreview, fn($r) => $r['hasWarnings'] ?? false);
                                } else {
                                    $filteredPreview = $importPreview;
                                }
                            ?>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                                <h4 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    Vista Previa (<?php echo e(count($importPreview)); ?> registros)
                                </h4>
                                
                                <div class="flex items-center gap-2">
                                    <button 
                                        wire:click="$set('importFilter', 'all')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors <?php echo e($importFilter === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'); ?>"
                                    >
                                        Todos
                                    </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invalidCount > 0): ?>
                                    <button 
                                        wire:click="$set('importFilter', 'errors')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors <?php echo e($importFilter === 'errors' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200'); ?>"
                                    >
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        Errores (<?php echo e($invalidCount); ?>)
                                    </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($warningCount > 0): ?>
                                    <button 
                                        wire:click="$set('importFilter', 'warnings')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors <?php echo e($importFilter === 'warnings' ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'); ?>"
                                    >
                                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Advertencias (<?php echo e($warningCount); ?>)
                                    </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <div class="overflow-x-auto max-h-64">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50 sticky top-0">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Fila</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Tipo</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Nombre</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Categoría</th>
                                                <th class="px-3 py-2 text-right font-semibold text-slate-500">Precio</th>
                                                <th class="px-3 py-2 text-center font-semibold text-slate-500">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $filteredPreview; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <?php
                                                $hasWarnings = $row['hasWarnings'] ?? false;
                                                $bgClass = !$row['valid'] ? 'bg-red-50' : ($hasWarnings ? 'bg-amber-50' : 'bg-white');
                                            ?>
                                            <tr class="<?php echo e($bgClass); ?>">
                                                <td class="px-3 py-2 text-slate-600"><?php echo e($row['row']); ?></td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo e(strtoupper($row['data']['tipo'] ?? '') === 'PADRE' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'); ?>">
                                                        <?php echo e($row['data']['tipo'] ?? '-'); ?>

                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-slate-900 max-w-xs truncate"><?php echo e($row['data']['nombre'] ?? '-'); ?></td>
                                                <td class="px-3 py-2 text-slate-600 max-w-xs truncate text-xs"><?php echo e($row['data']['categoria'] ?? '-'); ?></td>
                                                <td class="px-3 py-2 text-right text-slate-600">$<?php echo e(number_format(floatval($row['data']['precio_venta'] ?? 0), 0, ',', '.')); ?></td>
                                                <td class="px-3 py-2 text-center">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$row['valid']): ?>
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            Error
                                                        </span>
                                                        <span class="text-xs text-red-600 max-w-xs" title="<?php echo e(implode(', ', $row['errors'])); ?>">
                                                            <?php echo e(implode(', ', $row['errors'])); ?>

                                                        </span>
                                                    </div>
                                                    <?php elseif($hasWarnings): ?>
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                            Advertencia
                                                        </span>
                                                        <span class="text-xs text-amber-600 max-w-xs" title="<?php echo e(implode(', ', $row['warnings'] ?? [])); ?>">
                                                            <?php echo e(implode(', ', $row['warnings'] ?? [])); ?>

                                                        </span>
                                                    </div>
                                                    <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Válido
                                                    </span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($importFilter === 'errors'): ?>
                                                    <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    No hay errores, todos los registros son válidos
                                                    <?php elseif($importFilter === 'warnings'): ?>
                                                    <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    No hay advertencias
                                                    <?php else: ?>
                                                    No hay registros para mostrar
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-4 text-sm">
                                <span class="text-green-600">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <?php echo e($validCount); ?> válidos
                                </span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invalidCount > 0): ?>
                                <span class="text-red-600">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    <?php echo e($invalidCount); ?> con errores
                                </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($warningCount > 0): ?>
                                <span class="text-amber-600">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <?php echo e($warningCount); ?> con advertencias
                                </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isImporting): ?>
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-6" wire:poll.500ms>
                            <div class="text-center mb-4">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] mb-3">
                                    <svg class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-slate-800">Importando productos...</h4>
                                <p class="text-slate-500 text-sm mt-1">Por favor espere, no cierre esta ventana</p>
                            </div>
                            
                            
                            <?php
                                $percentage = $importTotal > 0 ? round(($importProgress / $importTotal) * 100) : 0;
                            ?>
                            <div class="text-center mb-3">
                                <span class="text-4xl font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent"><?php echo e($percentage); ?>%</span>
                            </div>
                            
                            
                            <div class="w-full bg-slate-200 rounded-full h-4 overflow-hidden mb-3">
                                <div 
                                    class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] h-4 rounded-full transition-all duration-300 relative"
                                    style="width: <?php echo e($percentage); ?>%"
                                >
                                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                </div>
                            </div>
                            
                            
                            <div class="text-center text-sm text-slate-600">
                                <span class="font-semibold"><?php echo e($importProgress); ?></span> de <span class="font-semibold"><?php echo e($importTotal); ?></span> productos procesados
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($importProcessed): ?>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-slate-800 mb-1">Resultado de la Importación</h4>
                                    <div class="flex items-center gap-4 text-sm">
                                        <span class="text-green-600">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <?php echo e($importSuccessCount); ?> importados
                                        </span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($importErrorCount > 0): ?>
                                        <span class="text-red-600">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            <?php echo e($importErrorCount); ?> con errores
                                        </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                                <button wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$importProcessed): ?>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <?php
                            $canImport = count($importPreview) > 0 && count(array_filter($importPreview, fn($r) => $r['valid'])) > 0;
                            $needsBranch = $needsBranchSelection && !$filterBranch;
                        ?>
                        <button 
                            wire:click="executeImport" 
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50 disabled:cursor-not-allowed"
                            <?php echo e(!$canImport || $needsBranch ? 'disabled' : ''); ?>

                        >
                            <span wire:loading.remove wire:target="executeImport">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Importar Productos
                            </span>
                            <span wire:loading wire:target="executeImport">
                                <svg class="w-4 h-4 inline mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Importando...
                            </span>
                        </button>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isBarcodeModalOpen): ?>
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeBarcodeModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Códigos de Barras</h3>
                            <p class="text-sm text-slate-500 mt-0.5"><?php echo e($barcodeProductName); ?></p>
                        </div>
                        <button wire:click="closeBarcodeModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    
                    <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($productBarcodes) > 0): ?>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Códigos registrados</label>
                            <div class="space-y-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $productBarcodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $barcode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200 <?php echo e($barcode['is_primary'] ? 'ring-2 ring-purple-500 ring-offset-1' : ''); ?>">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm text-slate-900"><?php echo e($barcode['barcode']); ?></span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($barcode['is_primary']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                                Principal
                                            </span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($barcode['description']): ?>
                                        <p class="text-xs text-slate-500 mt-0.5 truncate"><?php echo e($barcode['description']); ?></p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-1 ml-2">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$barcode['is_primary']): ?>
                                        <button wire:click="setPrimaryBarcode(<?php echo e($barcode['id']); ?>)" class="p-1.5 text-slate-400 hover:text-purple-500 hover:bg-purple-50 rounded-lg transition-colors" title="Establecer como principal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                        </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <button wire:click="deleteBarcode(<?php echo e($barcode['id']); ?>)" wire:confirm="¿Eliminar este código de barras?" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-6 text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            <p class="text-sm">No hay códigos de barras registrados</p>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <div class="border-t border-slate-200 pt-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Agregar nuevo código</label>
                            <div class="space-y-3">
                                <div>
                                    <input type="text" wire:model="newBarcode" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Código de barras">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['newBarcode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <input type="text" wire:model="newBarcodeDescription" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción (opcional)">
                                </div>
                                <button wire:click="addBarcode" class="w-full px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Agregar Código
                                </button>
                            </div>
                        </div>
                    </div>

                    
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="closeBarcodeModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isBulkDeleteModalOpen): ?>
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeBulkDeleteModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Eliminación Masiva de Productos</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Filtra y selecciona los productos a eliminar</p>
                        </div>
                        <button wire:click="closeBulkDeleteModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    
                    <div class="px-6 py-4 border-b border-slate-200 space-y-3">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input wire:model.live.debounce.300ms="bulkDeleteSearch" type="text" class="block w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm" placeholder="Buscar por nombre o SKU...">
                            </div>
                            <select wire:model.live="bulkDeleteStockFilter" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                                <option value="">Stock: Todos</option>
                                <option value="zero">Sin stock (0)</option>
                                <option value="low">Stock bajo</option>
                                <option value="has">Con stock</option>
                            </select>
                            <select wire:model.live="bulkDeleteStatusFilter" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                                <option value="">Estado: Todos</option>
                                <option value="1">Activos</option>
                                <option value="0">Inactivos</option>
                            </select>
                            <select wire:model.live="bulkDeleteCategoryFilter" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                                <option value="">Categoría: Todas</option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>"><?php echo e($cat->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </select>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button wire:click="selectAllBulkDelete" class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                    Seleccionar todos
                                </button>
                                <button wire:click="deselectAllBulkDelete" class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                    Deseleccionar todos
                                </button>
                            </div>
                            <span class="text-sm font-medium <?php echo e(count($bulkDeleteSelected) > 0 ? 'text-red-600' : 'text-slate-500'); ?>">
                                <?php echo e(count($bulkDeleteSelected)); ?> seleccionado(s)
                            </span>
                        </div>
                    </div>

                    
                    <div class="px-6 py-4 max-h-[400px] overflow-y-auto">
                        <?php $bulkProducts = $this->bulkDeleteProducts; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bulkProducts->count() > 0): ?>
                        <div class="space-y-1">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $bulkProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $hasActiveChildren = $product->active_children_count > 0; ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl transition-colors <?php echo e($hasActiveChildren ? 'opacity-50 cursor-not-allowed bg-slate-50' : 'hover:bg-slate-50 cursor-pointer'); ?> <?php echo e(in_array($product->id, $bulkDeleteSelected) ? 'bg-red-50 border border-red-200' : 'border border-transparent'); ?>">
                                <input
                                    type="checkbox"
                                    wire:click="toggleBulkDeleteProduct(<?php echo e($product->id); ?>)"
                                    <?php echo e(in_array($product->id, $bulkDeleteSelected) ? 'checked' : ''); ?>

                                    <?php echo e($hasActiveChildren ? 'disabled' : ''); ?>

                                    class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500"
                                >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-sm text-slate-900 truncate"><?php echo e($product->name); ?></span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->category): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 flex-shrink-0"><?php echo e($product->category->name); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-500 mt-0.5">
                                        <span>SKU: <?php echo e($product->sku ?? '-'); ?></span>
                                        <span>Stock: <?php echo e(rtrim(rtrim(number_format($product->current_stock, 3), '0'), '.')); ?></span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs <?php echo e($product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                                            <?php echo e($product->is_active ? 'Activo' : 'Inactivo'); ?>

                                        </span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasActiveChildren): ?>
                                        <span class="text-amber-600 font-medium">Tiene variantes activas</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8 text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="text-sm">No se encontraron productos con los filtros aplicados</p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($needsBranchSelection && !$filterBranch): ?>
                            <p class="text-xs text-amber-600 mt-1">Selecciona una sucursal en los filtros principales primero</p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <p class="text-xs text-slate-500">Los productos con variantes activas no pueden eliminarse</p>
                        <div class="flex gap-3">
                            <button wire:click="closeBulkDeleteModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                                Cancelar
                            </button>
                            <button
                                wire:click="executeBulkDelete"
                                wire:loading.attr="disabled"
                                wire:target="executeBulkDelete"
                                <?php echo e(empty($bulkDeleteSelected) ? 'disabled' : ''); ?>

                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span wire:loading.remove wire:target="executeBulkDelete">
                                    Eliminar <?php echo e(count($bulkDeleteSelected)); ?> producto(s)
                                </span>
                                <span wire:loading wire:target="executeBulkDelete" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Eliminando...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\mikpos_v1.1\resources\views/livewire/products.blade.php ENDPATH**/ ?>