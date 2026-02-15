# MikPOS - Project Structure

Root application is in the workspace root directory.

## Key Directories

```
/
├── app/
│   ├── Console/Commands/    # Custom Artisan commands
│   ├── Http/Controllers/    # Traditional controllers (minimal use)
│   ├── Http/Middleware/     # Custom middleware (CheckPermission, CheckInstallation)
│   ├── Livewire/            # Livewire components (primary UI logic)
│   │   ├── Auth/            # Authentication components
│   │   └── Reports/         # Report components
│   ├── Models/              # Eloquent models
│   ├── Providers/           # Service providers
│   ├── Services/            # Business logic services
│   └── View/Components/     # Blade view components
├── config/                  # Laravel configuration files
├── database/
│   ├── migrations/          # Database migrations
│   ├── seeders/             # Database seeders
│   └── factories/           # Model factories for testing
├── resources/
│   ├── css/                 # Tailwind CSS entry point
│   ├── js/                  # JavaScript entry point
│   └── views/
│       ├── components/      # Reusable Blade components
│       ├── layouts/         # Layout templates (app.blade.php, guest.blade.php)
│       ├── livewire/        # Livewire component views
│       │   └── reports/     # Report views
│       └── receipts/        # Printable receipts (thermal 80mm)
├── routes/
│   └── web.php              # Web routes (Livewire components as routes)
```

## Current Livewire Components

### Authentication
- **Auth/Login** - User authentication

### Administration
- **Users** - User management with role assignment
- **Branches** - Multi-branch management
- **Roles** - Role and permission management
- **ActivityLogs** - Activity log viewer with filters (user, branch, module, action, dates)

### Configuration
- **BillingSettings** - Electronic invoicing configuration (Factus/DIAN)
- **Departments** - Geographic departments
- **Municipalities** - Geographic municipalities
- **TaxDocuments** - Tax document types
- **Currencies** - Currency management
- **PaymentMethods** - Payment methods
- **Taxes** - Tax rates
- **SystemDocuments** - System document types
- **ProductFieldConfig** - Product field configuration

### Product Catalog
- **Categories** - Product categories
- **Subcategories** - Product subcategories
- **Brands** - Product brands
- **Units** - Units of measurement
- **ProductModels** - Product models
- **Presentations** - Product presentations
- **Colors** - Product colors
- **Imeis** - IMEI management

### Cash Management
- **CashRegisters** - Cash register creation and management
- **CashReconciliations** - Cash reconciliations (arqueos) with movements, edit history

### Point of Sale
- **PointOfSale** - Full POS interface with barcode scanning, customer search/creation
- **Sales** - Sales listing with refunds and credit notes

### Creation/Catalog
- **Products** - Product management with variants
- **Services** - Service management (no inventory)
- **Customers** - Customer management
- **Suppliers** - Supplier management
- **Combos** - Combo products
- **Credits** - Credit/payment management

### Inventory
- **Purchases** - Purchase order listing and payment control
- **PurchaseCreate** - Purchase order creation with inline discounts
- **InventoryAdjustments** - Inventory adjustments
- **InventoryTransfers** - Inventory transfers between branches

### Reports
- **Reports/SalesBook** - Complete sales book report
- **Reports/ProductsSold** - Products sold report with filters
- **Reports/Commissions** - Sales commissions report
- **Reports/ProfitLoss** - Profit and loss report
- **Reports/CreditsReport** - Credits/receivables report
- **Reports/PurchasesReport** - Purchases report (7 tabs, 3 charts)
- **Reports/CashReport** - Cash report (arqueos, movements, consolidated)
- **Reports/Kardex** - Inventory kardex report

## Current Models

### Core
- User, Role, Permission, Module
- Branch, ActivityLog

### Geographic
- Department, Municipality

### Configuration
- BillingSetting, TaxDocument, Currency, PaymentMethod, Tax
- SystemDocument, ProductFieldSetting

### Product Catalog
- Category, Subcategory, Brand, Unit
- ProductModel, Presentation, Color, Imei
- Product, ProductChild, ProductBarcode

### Services
- Service

### Cash Management
- CashRegister, CashReconciliation, CashReconciliationEdit, CashMovement

### Sales & Transactions
- Sale, SaleItem, SalePayment, SaleReprint
- Customer, Supplier
- Combo, ComboItem
- Purchase, PurchaseItem
- CreditPayment, CreditNote, CreditNoteItem
- Refund, RefundItem
- InventoryMovement

## Database Tables

### Core
- users, roles, permissions, modules
- user_role (pivot), permission_role (pivot)
- branches, activity_logs

### Geographic
- departments, municipalities

### Configuration
- billing_settings, tax_documents, currencies, payment_methods, taxes
- system_documents, product_field_settings

### Product Catalog
- categories, subcategories, brands, units
- product_models, presentations, colors, imeis
- products, product_children, product_barcodes

### Services
- services

### Cash Management
- cash_registers, cash_reconciliations, cash_reconciliation_edits, cash_movements

### Sales & Transactions
- sales, sale_items, sale_payments, sale_reprints
- customers, suppliers
- combos, combo_items
- purchases, purchase_items
- credit_payments, credit_notes, credit_note_items
- refunds, refund_items
- inventory_movements
- seeder_history (deployment tracking)

## Conventions

### Livewire Components
- Full-page components use `#[Layout('layouts.app')]` attribute
- Guest pages use `#[Layout('layouts.guest')]` attribute
- Views in `resources/views/livewire/` mirror component namespace
- Use Livewire attributes for validation: `#[Rule('required|min:3')]`
- Follow CRUD pattern: create(), edit(), store(), delete(), toggleStatus()

### Models
- Located in `app/Models/`
- Use `$fillable` for mass assignment protection
- Use `$casts` for attribute casting (especially boolean fields)
- Define relationships as methods
- Follow Laravel naming conventions

### Views
- Blade templates with Tailwind CSS classes
- Modals rendered inline within Livewire components
- Use `wire:` directives for Livewire bindings
- Use `x-data`, `x-show`, `@click` for Alpine.js interactivity
- Consistent UI patterns across all modules

### Reusable Blade Components

#### Searchable Select (`x-searchable-select`)
Select con buscador usando Alpine.js puro y Tailwind CSS. Compatible con Livewire.

**Ubicación:** `resources/views/components/searchable-select.blade.php`

**Props:**
- `options` - Array de objetos `[{id: 1, name: 'Texto'}, ...]`
- `placeholder` - Texto cuando no hay selección (default: 'Seleccionar...')
- `searchPlaceholder` - Texto en el input de búsqueda (default: 'Buscar...')
- `displayKey` - Clave para mostrar (default: 'name')
- `valueKey` - Clave para el valor (default: 'id')
- `disabled` - Estado deshabilitado (default: false)

**Uso:**
```blade
<x-searchable-select
    wire:model="department_id"
    :options="$departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name])->toArray()"
    placeholder="Seleccionar departamento..."
    searchPlaceholder="Buscar departamento..."
/>
```

**IMPORTANTE:** NO usar jQuery ni librerías externas. Solo Alpine.js (incluido con Livewire) y Tailwind CSS.

### Notifications
- Use `$this->dispatch('notify', message: 'Message', type: 'success')` in Livewire
- Types: success, error, warning, info
- Toast component in `resources/views/components/toast.blade.php`

### Activity Logging
- Use `ActivityLogService::logCreate/logUpdate/logDelete()` for CRUD operations
- **IMPORTANT**: Must pass Eloquent Model instance, not stdClass
- Logs stored in `activity_logs` table with old/new values
- Automatic logging for all entity changes

### Routing
- Livewire components registered directly as routes in `routes/web.php`
- Protected routes use `auth` middleware
- Permission-based route protection: `middleware('permission:module.view')`
- Guest routes use `guest` middleware

### Menu Structure (Sidebar)
Located in `resources/views/components/sidebar-menu.blade.php`

```
Dashboard
POS
Cajas (independent section)
├── Creación de Cajas
└── Arqueos de Caja
Administración
├── Usuarios
├── Sucursales
├── Roles
├── Logs de Actividad
└── Configuración
    ├── Departamentos
    ├── Municipios
    ├── Documentos Tributarios
    ├── Monedas
    ├── Métodos de Pago
    ├── Impuestos
    ├── Documentos Sistema
    ├── Config. Campos Producto
    └── Productos
        ├── Categorías
        ├── Subcategorías
        ├── Marcas
        ├── Unidades
        ├── Modelos
        ├── Presentaciones
        ├── Colores
        └── IMEIs
Creación
├── Productos
├── Servicios
├── Clientes
├── Proveedores
└── Combos
Inventarios
├── Compras
├── Ajustes Inventario
└── Transferencias
Reportes
├── Ventas
│   ├── Libro de Ventas
│   ├── Productos Vendidos
│   ├── Comisiones
│   ├── Utilidades
│   ├── Créditos
│   ├── Compras
│   └── Cajas
└── Inventario
    └── Kardex
```


## Console Commands

### Custom Artisan Commands
Located in `app/Console/Commands/`

- **SeedPending** (`db:seed-pending`) - Run only new/pending seeders
- **SeedMarkExecuted** (`db:seed-mark-executed`) - Mark seeders as already executed
- **FixUtcDates** (`fix:utc-dates`) - Convert UTC dates to America/Bogota timezone
- **FixExistingRefunds** (`fix:existing-refunds`) - Fix inventory for existing refunds
- **GenerateSaleInventoryMovements** - Generate missing sale inventory movements

### Common Commands

```bash
# Development
composer dev              # Start dev server
npm run build            # Build assets

# Database
php artisan migrate                    # Run migrations
php artisan migrate:fresh --seed       # Fresh DB with seeders
php artisan db:seed-pending --force    # Run only new seeders

# Cache
php artisan optimize:clear   # Clear all caches
php artisan optimize         # Cache config, routes, views

# Code Quality
./vendor/bin/pint           # Format code
php artisan pail            # View logs
```

## Deployment

### Deploy Script
Located at `deploy.sh` in project root.

```bash
./deploy.sh   # Run from server
```

### Docker Commands (Production)
```bash
docker compose exec -T php php artisan migrate --force
docker compose exec -T php php artisan db:seed-pending --force
docker compose exec -T php php artisan optimize
```


## Services Module

### Overview
Services are similar to products but without inventory/stock management.

### Service Model Fields
- `branch_id`: Branch ownership
- `sku`: Auto-generated (SRV-XXXXX)
- `name`, `description`: Basic info
- `category_id`: Optional category
- `tax_id`: Tax rate
- `cost`, `sale_price`: Pricing
- `price_includes_tax`: Boolean
- `has_commission`, `commission_type`, `commission_value`: Commission settings
- `image`: Optional image
- `is_active`: Status

### Key Differences from Products
- No barcode
- No stock/inventory tracking
- No variants/children
- Unlimited quantity in POS (no stock check)
- SKU prefix: SRV- instead of category-based

### POS Integration
- Services appear in the same grid as products
- Identified by "Serv." badge (indigo color) at top-left
- Stock info NOT shown for services (only for products)
- Uses `addServiceToCart()` method
- `sale_items.service_id` stores the service reference
