# MikPOS - Product Catalog Module

Complete product catalog management system with hierarchical organization and optional attributes.

## Module Structure

### Core Components (Required)
1. **Categories** (`/categories`)
   - Main product groupings (Electronics, Clothing, Food, Beverages)
   - Fields: name, description, image (optional), is_active
   - Has many subcategories

2. **Subcategories** (`/subcategories`) 
   - Category subdivisions (Cellphones within Electronics, Shirts within Clothing)
   - Fields: category_id, name, description, is_active
   - Belongs to category, filterable by category

3. **Brands** (`/brands`)
   - Product manufacturers/brands (Samsung, Nike, Coca-Cola)
   - Fields: name, logo (optional), is_active
   - Has many product models, shows model count

4. **Units** (`/units`)
   - Measurement units for quantities
   - Fields: name, abbreviation (auto-uppercase), is_active
   - Examples: Unidad (UND), Kilogramo (KG), Litro (LT), Caja (CJ)

### Optional Components
5. **Product Models** (`/product-models`)
   - Specific product models linked to brands
   - Fields: brand_id (nullable), name, description, is_active
   - Examples: Galaxy S24 (Samsung), iPhone 15 (Apple)

6. **Presentations** (`/presentations`)
   - Product presentation formats
   - Fields: name, description, is_active
   - Examples: Caja x12, Blister x10, Individual

7. **Colors** (`/colors`)
   - Product color variants with visual representation
   - Fields: name, hex_code (optional), is_active
   - Visual color picker and HEX code input

8. **IMEIs** (`/imeis`)
   - Device IMEI number management
   - Fields: imei (15-17 digits), imei2 (optional), status, notes
   - Status: available, sold, reserved
   - Validation for IMEI format

## Database Relationships

```
Category (1) → (N) Subcategory
Brand (1) → (N) ProductModel
```

## Permissions Structure

Each module has standard CRUD permissions:
- `{module}.view` - View list and details
- `{module}.create` - Create new records
- `{module}.edit` - Edit existing records (includes toggle status)
- `{module}.delete` - Delete records

**Permission Names:**
- categories.view/create/edit/delete
- subcategories.view/create/edit/delete
- brands.view/create/edit/delete
- units.view/create/edit/delete
- product_models.view/create/edit/delete
- presentations.view/create/edit/delete
- colors.view/create/edit/delete
- imeis.view/create/edit/delete

## UI Features

### Common Features (All Modules)
- Search functionality with live filtering
- Pagination (10 items per page)
- Status toggle (active/inactive) with visual switches
- Create/Edit modals with form validation
- Delete confirmation modals
- Responsive design with mobile support
- Activity logging for all operations

### Module-Specific Features
- **Subcategories**: Category filter dropdown
- **Brands**: Product model count display
- **Units**: Automatic abbreviation uppercase conversion
- **Product Models**: Brand filter dropdown, optional brand assignment
- **Colors**: Visual color picker, HEX code validation
- **IMEIs**: Status filtering, IMEI format validation

## Navigation Structure

Located in sidebar under: **Administración → Configuración → Productos**

```
Administración
└── Configuración
    ├── Departamentos
    ├── Municipios
    ├── Documentos Tributarios
    ├── Monedas
    ├── Métodos de Pago
    ├── Impuestos
    └── Productos
        ├── Categorías
        ├── Subcategorías  
        ├── Marcas
        ├── Unidades de Medida
        ├── Modelos (opcional)
        ├── Presentaciones (opcional)
        ├── Colores (opcional)
        └── IMEIs (opcional)
```

## Implementation Pattern

All modules follow consistent Livewire component structure:

### Component Methods
- `render()` - Main view with search/filter logic
- `create()` - Open create modal
- `edit($id)` - Open edit modal with data
- `store()` - Save/update record
- `confirmDelete($id)` - Open delete confirmation
- `delete()` - Delete record
- `toggleStatus($id)` - Toggle active status
- `resetForm()` - Clear form data

### Validation Rules
- Name fields: required, minimum 2 characters
- Unique constraints where appropriate
- Relationship validation (foreign keys)
- Format validation (IMEI, HEX codes)

### Activity Logging
All CRUD operations automatically logged via `ActivityLogService`:
- Create: "Categoría 'Electronics' creada"
- Update: "Marca 'Samsung' actualizada" 
- Delete: "Unidad 'Kilogramo' eliminada"
- Status: "Color 'Rojo' activado/desactivado"

## File Structure

```
app/
├── Livewire/
│   ├── Categories.php
│   ├── Subcategories.php
│   ├── Brands.php
│   ├── Units.php
│   ├── ProductModels.php
│   ├── Presentations.php
│   ├── Colors.php
│   └── Imeis.php
├── Models/
│   ├── Category.php
│   ├── Subcategory.php
│   ├── Brand.php
│   ├── Unit.php
│   ├── ProductModel.php
│   ├── Presentation.php
│   ├── Color.php
│   └── Imei.php
resources/views/livewire/
├── categories.blade.php
├── subcategories.blade.php
├── brands.blade.php
├── units.blade.php
├── product-models.blade.php
├── presentations.blade.php
├── colors.blade.php
└── imeis.blade.php
```