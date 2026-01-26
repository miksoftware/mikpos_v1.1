# MikPOS - Project Structure

Root application is in the workspace root directory.

## Key Directories

```
/
├── app/
│   ├── Http/Controllers/    # Traditional controllers (minimal use)
│   ├── Http/Middleware/     # Custom middleware (CheckPermission)
│   ├── Livewire/            # Livewire components (primary UI logic)
│   │   └── Auth/            # Authentication components
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
│       └── livewire/        # Livewire component views
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

### Configuration
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
- **CashReconciliations** - Cash reconciliations (arqueos) with movements

### Creation/Catalog
- **Products** - Product management
- **Customers** - Customer management
- **Suppliers** - Supplier management
- **Combos** - Combo products

### Inventory
- **Purchases** - Purchase order listing and payment control
- **PurchaseCreate** - Purchase order creation
- **InventoryAdjustments** - Inventory adjustments
- **InventoryTransfers** - Inventory transfers between branches

## Current Models

### Core
- User, Role, Permission, Module
- Branch, ActivityLog

### Geographic
- Department, Municipality

### Configuration
- TaxDocument, Currency, PaymentMethod, Tax
- SystemDocument, ProductFieldSetting

### Product Catalog
- Category, Subcategory, Brand, Unit
- ProductModel, Presentation, Color, Imei
- Product, ProductChild

### Cash Management
- CashRegister, CashReconciliation, CashMovement

### Transactions
- Customer, Supplier
- Combo, ComboItem
- Purchase, PurchaseItem
- InventoryMovement

## Database Tables

### Core
- users, roles, permissions, modules
- user_role (pivot), permission_role (pivot)
- branches, activity_logs

### Geographic
- departments, municipalities

### Configuration
- tax_documents, currencies, payment_methods, taxes
- system_documents, product_field_settings

### Product Catalog
- categories, subcategories, brands, units
- product_models, presentations, colors, imeis
- products, product_children

### Cash Management
- cash_registers, cash_reconciliations, cash_movements

### Transactions
- customers, suppliers
- combos, combo_items
- purchases, purchase_items
- inventory_movements

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
Located in `resources/views/layouts/app.blade.php`

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
├── Clientes
├── Proveedores
└── Combos
Inventarios
├── Compras
├── Ajustes Inventario
└── Transferencias
```
