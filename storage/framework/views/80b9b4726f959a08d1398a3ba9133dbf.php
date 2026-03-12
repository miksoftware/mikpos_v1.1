<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'options' => [],
    'placeholder' => 'Seleccionar...',
    'searchPlaceholder' => 'Buscar...',
    'displayKey' => 'name',
    'valueKey' => 'id',
    'disabled' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'options' => [],
    'placeholder' => 'Seleccionar...',
    'searchPlaceholder' => 'Buscar...',
    'displayKey' => 'name',
    'valueKey' => 'id',
    'disabled' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $optionsJson = Js::from($options);
?>

<div
    x-data="{
        open: false,
        search: '',
        selected: <?php if ((object) ($attributes->wire('model')) instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($attributes->wire('model')->value()); ?>')<?php echo e($attributes->wire('model')->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e($attributes->wire('model')); ?>')<?php endif; ?>,
        options: <?php echo e($optionsJson); ?>,
        displayKey: '<?php echo e($displayKey); ?>',
        valueKey: '<?php echo e($valueKey); ?>',
        
        get filteredOptions() {
            if (!this.search) return this.options;
            const searchLower = this.search.toLowerCase();
            return this.options.filter(option => 
                option[this.displayKey].toLowerCase().includes(searchLower)
            );
        },
        
        get selectedOption() {
            if (!this.selected) return null;
            return this.options.find(option => 
                String(option[this.valueKey]) === String(this.selected)
            );
        },
        
        get displayText() {
            return this.selectedOption ? this.selectedOption[this.displayKey] : '<?php echo e($placeholder); ?>';
        },
        
        selectOption(option) {
            this.selected = option[this.valueKey];
            this.open = false;
            this.search = '';
        },
        
        clearSelection() {
            this.selected = '';
            this.search = '';
        },
        
        closeDropdown() {
            this.open = false;
            this.search = '';
        },
        
        updateOptions(newOptions) {
            this.options = newOptions;
            this.search = '';
        }
    }"
    x-init="$watch('$wire.<?php echo e($attributes->wire('model')->value()); ?>', value => selected = value)"
    @click.away="closeDropdown()"
    @keydown.escape.window="closeDropdown()"
    class="relative"
    wire:ignore.self
>
    <!-- Trigger Button -->
    <button
        type="button"
        @click="open = !open"
        <?php echo e($disabled ? 'disabled' : ''); ?>

        class="relative w-full cursor-pointer rounded-xl border border-slate-300 bg-white py-2.5 pl-3 pr-10 text-left text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:border-[#ff7261] focus:outline-none focus:ring-2 focus:ring-[#ff7261]/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"
        :class="{ 'border-[#ff7261] ring-2 ring-[#ff7261]/20': open }"
    >
        <span 
            class="block truncate"
            :class="selectedOption ? 'text-slate-900' : 'text-slate-400'"
            x-text="displayText"
        ></span>
        
        <!-- Clear button -->
        <span 
            x-show="selectedOption" 
            @click.stop="clearSelection()"
            class="absolute inset-y-0 right-8 flex items-center pr-1 cursor-pointer"
        >
            <svg class="h-4 w-4 text-slate-400 hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </span>
        
        <!-- Chevron -->
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <svg 
                class="h-5 h-5 text-slate-400 transition-transform duration-200" 
                :class="{ 'rotate-180': open }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </span>
    </button>

    <!-- Dropdown -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5"
        style="display: none;"
        @click.stop
    >
        <!-- Search Input -->
        <div class="p-2">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    x-model="search"
                    x-ref="searchInput"
                    @click.stop
                    type="text"
                    class="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm placeholder-slate-400 focus:border-[#ff7261] focus:outline-none focus:ring-1 focus:ring-[#ff7261]"
                    placeholder="<?php echo e($searchPlaceholder); ?>"
                >
            </div>
        </div>

        <!-- Options List -->
        <ul class="max-h-60 overflow-auto py-1">
            <template x-for="option in filteredOptions" :key="option[valueKey]">
                <li
                    @click="selectOption(option)"
                    class="relative cursor-pointer select-none px-3 py-2 text-sm transition-colors duration-100"
                    :class="{
                        'bg-[#ff7261]/10 text-[#ff7261]': String(selected) === String(option[valueKey]),
                        'text-slate-900 hover:bg-slate-50': String(selected) !== String(option[valueKey])
                    }"
                >
                    <span class="block truncate" x-text="option[displayKey]"></span>
                    
                    <!-- Check icon for selected -->
                    <span 
                        x-show="String(selected) === String(option[valueKey])"
                        class="absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                        <svg class="h-4 w-4 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                </li>
            </template>
            
            <!-- No results message -->
            <li 
                x-show="filteredOptions.length === 0" 
                class="px-3 py-2 text-sm text-slate-500 text-center"
            >
                No se encontraron resultados
            </li>
        </ul>
    </div>
</div>
<?php /**PATH C:\laragon\www\mikpos_v1.1\resources\views/components/searchable-select.blade.php ENDPATH**/ ?>