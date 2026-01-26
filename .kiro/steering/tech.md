# MikPOS - Tech Stack

## Backend
- PHP 8.2+
- Laravel 12.x
- Livewire 3.x (full-page components with attributes)
- SQLite (default, configurable)

## Frontend
- Tailwind CSS 4.x (via Vite plugin)
- Alpine.js (bundled with Livewire)
- Vite 7.x for asset bundling
- Inter font family

## Key Dependencies
- `livewire/livewire` ^3.6 - Reactive UI components
- `laravel/pint` ^1.24 - Code formatting
- `laravel/tinker` ^2.10.1 - Interactive shell
- `laravel/sail` ^1.41 - Docker development environment
- `laravel/pail` ^1.2.2 - Log viewer
- `nunomaduro/collision` ^8.6 - Error reporting

## Architecture
- **MVC Pattern**: Models for data, Livewire components for controllers/views
- **Service Layer**: `ActivityLogService` for centralized logging
- **Permission System**: Role-based access control with granular permissions
- **Multi-tenancy**: Branch-based data filtering and access control

## Database Schema
- **Core Tables**: users, roles, permissions, modules, branches, activity_logs
- **Geographic**: departments, municipalities
- **Configuration**: tax_documents, currencies, payment_methods, taxes, system_documents, product_field_settings
- **Product Catalog**: categories, subcategories, brands, units, product_models, presentations, colors, imeis, products, product_children
- **Cash Management**: cash_registers, cash_reconciliations, cash_movements
- **Transactions**: customers, suppliers, combos, combo_items, purchases, purchase_items, inventory_movements
- **Relationships**: Proper foreign key constraints and cascading deletes

## Common Commands

All commands run from the workspace root:

```bash
# Initial setup
composer setup

# Development
composer dev

# Build assets
npm run build

# Migrations
php artisan migrate
php artisan migrate:fresh --seed

# Code formatting
./vendor/bin/pint

# Interactive shell
php artisan tinker

# View logs
php artisan pail
```

## Development Workflow
1. **Database Changes**: Create migrations with proper naming convention
2. **New Features**: Create Livewire component + model + view + permissions
3. **Permissions**: Add to seeder and assign to roles
4. **Routes**: Register in `routes/web.php` with permission middleware
5. **Menu**: Update `resources/views/layouts/app.blade.php` sidebar
6. **Validation**: Use default test users (admin@mikpos.com/password)

## UI/UX Standards

### Color Scheme
- **Primary Gradient**: `from-[#ff7261] to-[#a855f7]` for buttons and accents
- **Hover States**: `from-[#e55a4a] to-[#9333ea]`
- **Menu Icon Hover**: `#a855f7` (purple) - consistent across all menu sections
- **Active States**: `bg-white/10 text-white` for selected items

### Modal Structure
```blade
@if($isModalOpen)
<div class="relative z-[100]" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
    <div class="fixed inset-0 z-[101] overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">Title</h3>
                    <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" ...>X icon</svg>
                    </button>
                </div>
                <!-- Content -->
                <div class="px-6 py-4 space-y-4">
                    <!-- Form fields -->
                </div>
                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
```

### Delete Confirmation Modal
```blade
@if($isDeleteModalOpen)
<div class="relative z-[100]" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
    <div class="fixed inset-0 z-[101] overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-red-600">Warning icon</svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Title</h3>
                <p class="text-slate-500 mb-6">Message</p>
                <div class="flex justify-center gap-3">
                    <button class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
```

### Form Input Styling
```blade
<input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
<select class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
```

### Table Structure
- Container: `bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden`
- Header: `bg-slate-50` with `text-sm font-semibold text-slate-500 uppercase`
- Rows: `hover:bg-slate-50/50 transition-colors`
- Status Toggle: Custom switch with gradient colors

### Cards for Selection (Income/Expense style)
```blade
<div class="grid grid-cols-2 gap-4">
    <button @click="type = 'income'" 
        :class="type === 'income' ? 'border-green-500 bg-green-50' : 'border-slate-200 hover:border-green-300'"
        class="p-4 rounded-xl border-2 transition-all">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-2">
            <svg class="w-5 h-5 text-green-600">Icon</svg>
        </div>
        <span class="font-medium text-green-700">Ingreso</span>
        <p class="text-xs text-slate-500 mt-1">Description</p>
    </button>
    <!-- Similar for expense with red colors -->
</div>
```

### Menu Section Pattern (Sidebar)
```blade
<div x-data="{ sectionOpen: {{ request()->routeIs('route1') || request()->routeIs('route2') ? 'true' : 'false' }} }">
    <button @click="sectionOpen = !sectionOpen"
        class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group text-slate-400 hover:bg-white/5 hover:text-white">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0 group-hover:text-[#a855f7]">Icon</svg>
            <span x-show="sidebarOpen" class="font-medium">Section Name</span>
        </div>
        <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="sectionOpen ? 'rotate-180' : ''">Chevron</svg>
    </button>
    <div x-show="sectionOpen && sidebarOpen" x-collapse class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">
        <!-- Sub-items -->
    </div>
</div>
```

**Important**: Menu parent buttons should NOT have active state styling (no gradient background when child is selected). Only child items show `bg-white/10 text-white` when active.

### Icons
- Use Heroicons (outline style)
- Size: `w-5 h-5` for main icons, `w-4 h-4` for sub-items, `w-3 h-3` for nested items

### Responsive Design
- Mobile-first approach
- Collapsible sidebar on desktop
- Mobile menu with hamburger toggle
- Grid layouts: `grid-cols-1 sm:grid-cols-2` pattern
