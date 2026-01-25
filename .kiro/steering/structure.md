# MikPOS - Project Structure

Root application is in the workspace root directory.

## Key Directories

```
/
├── app/
│   ├── Http/Controllers/    # Traditional controllers (minimal use)
│   ├── Livewire/            # Livewire components (primary UI logic)
│   │   └── Auth/            # Authentication components
│   ├── Models/              # Eloquent models
│   ├── Providers/           # Service providers
│   ├── Services/            # Business logic services
│   └── View/Components/     # Blade view components
├── config/                  # Laravel configuration files
├── database/
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
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
- **Auth/Login** - Authentication
- **Branches** - Multi-branch management
- **Users** - User management
- **Roles** - Role and permission management
- **Departments** - Geographic departments
- **Municipalities** - Geographic municipalities
- **TaxDocuments** - Tax document types
- **Currencies** - Currency management
- **PaymentMethods** - Payment methods
- **Taxes** - Tax rates
- **Product Catalog:**
  - **Categories** - Product categories
  - **Subcategories** - Product subcategories
  - **Brands** - Product brands
  - **Units** - Units of measurement
  - **ProductModels** - Product models
  - **Presentations** - Product presentations
  - **Colors** - Product colors
  - **Imeis** - IMEI management

## Current Models
- User, Role, Permission, Module
- Branch, ActivityLog
- Department, Municipality
- TaxDocument, Currency, PaymentMethod, Tax
- **Product Catalog:** Category, Subcategory, Brand, Unit, ProductModel, Presentation, Color, Imei

## Database Tables
- Core: users, roles, permissions, modules, role_user, permission_role
- System: branches, activity_logs, cache, jobs
- Geographic: departments, municipalities
- Configuration: tax_documents, currencies, payment_methods, taxes
- **Product Catalog:** categories, subcategories, brands, units, product_models, presentations, colors, imeis

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

**Características:**
- Filtrado client-side en tiempo real
- Animaciones suaves con `x-transition`
- Check en la opción seleccionada
- Botón para limpiar selección
- Mensaje "No se encontraron resultados"
- Cierre al hacer clic fuera (`@click.away`)
- Sincronización con Livewire via `@entangle`

**IMPORTANTE:** NO usar jQuery ni librerías externas. Solo Alpine.js (incluido con Livewire) y Tailwind CSS.

### Notifications
- Use `$this->dispatch('notify', message: 'Message', type: 'success')` in Livewire
- Types: success, error, warning, info
- Toast component in `resources/views/components/toast.blade.php`

### Activity Logging
- Use `ActivityLogService::logCreate/logUpdate/logDelete()` for CRUD operations
- Logs stored in `activity_logs` table with old/new values
- Automatic logging for all entity changes

### Routing
- Livewire components registered directly as routes in `routes/web.php`
- Protected routes use `auth` middleware
- Permission-based route protection: `middleware('permission:module.view')`
- Guest routes use `guest` middleware

### Menu Structure
- Sidebar navigation with collapsible sections
- **Administración** section contains:
  - Users, Branches, Roles
  - **Configuración** subsection: Departments, Municipalities, Tax Documents, Currencies, Payment Methods, Taxes
  - **Productos** subsection: Categories, Subcategories, Brands, Units, Models, Presentations, Colors, IMEIs
