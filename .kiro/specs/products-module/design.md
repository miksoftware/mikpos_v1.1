# Design Document: Products Module

## Overview

El módulo de productos implementa un sistema jerárquico de productos padre-hijo con campos configurables por tipo de negocio. Utiliza la arquitectura existente de Livewire 3.x con componentes full-page, siguiendo los patrones establecidos en MikPOS.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Products Module                          │
├─────────────────────────────────────────────────────────────────┤
│  Livewire Components                                            │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────────────────┐   │
│  │  Products   │  │ ProductForm │  │ ProductFieldConfig   │   │
│  │  (List)     │  │  (Modal)    │  │ (Settings)           │   │
│  └─────────────┘  └─────────────┘  └──────────────────────┘   │
├─────────────────────────────────────────────────────────────────┤
│  Models                                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────────────────┐   │
│  │  Product    │  │ProductChild │  │ ProductFieldSetting  │   │
│  │  (Parent)   │  │  (Variant)  │  │ (Config)             │   │
│  └─────────────┘  └─────────────┘  └──────────────────────┘   │
├─────────────────────────────────────────────────────────────────┤
│  Existing Models (Relations)                                    │
│  Category, Subcategory, Brand, Unit, Tax, Presentation,        │
│  Color, ProductModel, Branch                                    │
└─────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Products Livewire Component

```php
#[Layout('layouts.app')]
class Products extends Component
{
    use WithPagination;
    
    // Search and filters
    public string $search = '';
    public ?int $categoryFilter = null;
    public ?int $brandFilter = null;
    public ?string $statusFilter = null;
    
    // Modal states
    public bool $isModalOpen = false;
    public bool $isChildModalOpen = false;
    public bool $isDeleteModalOpen = false;
    
    // Form data
    public ?int $productId = null;
    public array $formData = [];
    public array $childFormData = [];
    
    // Methods
    public function render(): View;
    public function create(): void;
    public function edit(int $id): void;
    public function store(): void;
    public function createChild(int $parentId): void;
    public function editChild(int $id): void;
    public function storeChild(): void;
    public function confirmDelete(int $id): void;
    public function delete(): void;
    public function toggleStatus(int $id): void;
    public function toggleChildStatus(int $id): void;
}
```

### 2. ProductFieldConfig Livewire Component

```php
#[Layout('layouts.app')]
class ProductFieldConfig extends Component
{
    public array $fieldSettings = [];
    public ?string $selectedPreset = null;
    
    // Presets for common business types
    public const PRESETS = [
        'pharmacy' => [...],
        'cellphones' => [...],
        'clothing' => [...],
        'jewelry' => [...],
        'general' => [...],
    ];
    
    public function render(): View;
    public function saveSettings(): void;
    public function applyPreset(string $preset): void;
}
```

## Data Models

### Product (Parent) Table: `products`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, auto | ID único |
| sku | varchar(50) | unique, nullable | Código SKU |
| name | varchar(255) | required | Nombre del producto |
| description | text | nullable | Descripción |
| category_id | bigint | FK, required | Categoría |
| subcategory_id | bigint | FK, nullable | Subcategoría |
| brand_id | bigint | FK, nullable | Marca |
| unit_id | bigint | FK, required | Unidad base |
| tax_id | bigint | FK, nullable | Impuesto aplicable |
| image | varchar(255) | nullable | Ruta de imagen |
| is_active | boolean | default true | Estado activo |
| created_at | timestamp | auto | Fecha creación |
| updated_at | timestamp | auto | Fecha actualización |

### ProductChild (Variant) Table: `product_children`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, auto | ID único |
| product_id | bigint | FK, required | Producto padre |
| sku | varchar(50) | unique, nullable | SKU de variante |
| barcode | varchar(100) | unique, nullable | Código de barras |
| name | varchar(255) | required | Nombre variante |
| presentation_id | bigint | FK, nullable | Presentación |
| color_id | bigint | FK, nullable | Color |
| product_model_id | bigint | FK, nullable | Modelo |
| size | varchar(50) | nullable | Talla |
| weight | decimal(10,3) | nullable | Peso |
| purchase_price | decimal(12,2) | required | Precio compra |
| sale_price | decimal(12,2) | required | Precio venta |
| price_includes_tax | boolean | default false | Precio con IVA |
| min_stock | int | default 0 | Stock mínimo |
| max_stock | int | nullable | Stock máximo |
| current_stock | int | default 0 | Stock actual |
| image | varchar(255) | nullable | Imagen variante |
| imei | varchar(20) | nullable | IMEI (celulares) |
| is_active | boolean | default true | Estado activo |
| created_at | timestamp | auto | Fecha creación |
| updated_at | timestamp | auto | Fecha actualización |

### ProductFieldSetting Table: `product_field_settings`

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, auto | ID único |
| branch_id | bigint | FK, nullable | Sucursal (null=global) |
| field_name | varchar(50) | required | Nombre del campo |
| is_visible | boolean | default true | Campo visible |
| is_required | boolean | default false | Campo requerido |
| display_order | int | default 0 | Orden de visualización |
| created_at | timestamp | auto | Fecha creación |
| updated_at | timestamp | auto | Fecha actualización |

### Configurable Fields List

```php
const CONFIGURABLE_FIELDS = [
    'barcode' => ['label' => 'Código de Barras', 'default_visible' => true],
    'presentation_id' => ['label' => 'Presentación', 'default_visible' => true],
    'color_id' => ['label' => 'Color', 'default_visible' => false],
    'product_model_id' => ['label' => 'Modelo', 'default_visible' => false],
    'size' => ['label' => 'Talla', 'default_visible' => false],
    'weight' => ['label' => 'Peso', 'default_visible' => false],
    'imei' => ['label' => 'IMEI', 'default_visible' => false],
    'min_stock' => ['label' => 'Stock Mínimo', 'default_visible' => true],
    'max_stock' => ['label' => 'Stock Máximo', 'default_visible' => false],
];
```

### Business Type Presets

```php
const PRESETS = [
    'pharmacy' => [
        'presentation_id' => ['visible' => true, 'required' => true],
        'barcode' => ['visible' => true, 'required' => false],
        'color_id' => ['visible' => false],
        'size' => ['visible' => false],
        'imei' => ['visible' => false],
    ],
    'cellphones' => [
        'product_model_id' => ['visible' => true, 'required' => true],
        'color_id' => ['visible' => true, 'required' => true],
        'imei' => ['visible' => true, 'required' => false],
        'presentation_id' => ['visible' => false],
        'size' => ['visible' => false],
    ],
    'clothing' => [
        'color_id' => ['visible' => true, 'required' => true],
        'size' => ['visible' => true, 'required' => true],
        'presentation_id' => ['visible' => false],
        'imei' => ['visible' => false],
    ],
    'jewelry' => [
        'weight' => ['visible' => true, 'required' => true],
        'color_id' => ['visible' => true],
        'barcode' => ['visible' => true],
        'presentation_id' => ['visible' => false],
        'imei' => ['visible' => false],
    ],
    'general' => [
        'barcode' => ['visible' => true],
        'presentation_id' => ['visible' => true],
        'color_id' => ['visible' => true],
        'min_stock' => ['visible' => true],
    ],
];
```

## Entity Relationships

```
┌──────────────┐       ┌───────────────────┐
│   Product    │ 1───N │   ProductChild    │
│   (Parent)   │       │    (Variant)      │
└──────┬───────┘       └────────┬──────────┘
       │                        │
       │ N                      │ N
       │ │                      │ │
       ▼ 1                      ▼ 1
┌──────────────┐       ┌───────────────────┐
│   Category   │       │   Presentation    │
└──────────────┘       └───────────────────┘
       │
       │ 1
       ▼ N
┌──────────────┐       ┌───────────────────┐
│ Subcategory  │       │      Color        │
└──────────────┘       └───────────────────┘

┌──────────────┐       ┌───────────────────┐
│    Brand     │       │   ProductModel    │
└──────────────┘       └───────────────────┘

┌──────────────┐       ┌───────────────────┐
│     Unit     │       │       Tax         │
└──────────────┘       └───────────────────┘
```

## UI Design

### Products List View

```
┌─────────────────────────────────────────────────────────────────┐
│  Productos                                        [+ Nuevo]     │
├─────────────────────────────────────────────────────────────────┤
│  🔍 Buscar...          [Categoría ▼] [Marca ▼] [Estado ▼]      │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────┬────────────────────┬──────────┬────────┬───────────┐  │
│  │ IMG │ Producto           │ Categoría│ Marca  │ Variantes │  │
│  ├─────┼────────────────────┼──────────┼────────┼───────────┤  │
│  │ 📷  │ Acetaminofén       │ Medicinas│ Genfar │ 3 hijos   │  │
│  │     │ SKU: MED-001       │          │        │ [▼ Ver]   │  │
│  ├─────┼────────────────────┼──────────┼────────┼───────────┤  │
│  │     │  └─ Tableta x10    │          │        │ $5.00     │  │
│  │     │  └─ Caja x100      │          │        │ $45.00    │  │
│  │     │  └─ Blister x5     │          │        │ $2.50     │  │
│  ├─────┼────────────────────┼──────────┼────────┼───────────┤  │
│  │ 📷  │ iPhone 15 Pro      │ Celulares│ Apple  │ 2 hijos   │  │
│  │     │ SKU: CEL-015       │          │        │ [▼ Ver]   │  │
│  └─────┴────────────────────┴──────────┴────────┴───────────┘  │
│                                                                 │
│  [◀ Anterior]  Página 1 de 5  [Siguiente ▶]                    │
└─────────────────────────────────────────────────────────────────┘
```

### Product Parent Form Modal

```
┌─────────────────────────────────────────────────────────────────┐
│  Nuevo Producto                                          [X]    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📦 Información Básica                                          │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ SKU (opcional)          │ │ Nombre *                │       │
│  │ [________________]      │ │ [________________]      │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│  ┌─────────────────────────────────────────────────────┐       │
│  │ Descripción                                         │       │
│  │ [_______________________________________________]   │       │
│  └─────────────────────────────────────────────────────┘       │
│                                                                 │
│  📂 Clasificación                                               │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ Categoría *             │ │ Subcategoría            │       │
│  │ [Seleccionar...    ▼]   │ │ [Seleccionar...    ▼]   │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ Marca                   │ │ Unidad Base *           │       │
│  │ [Seleccionar...    ▼]   │ │ [Seleccionar...    ▼]   │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│  💰 Impuesto                                                    │
│  ┌─────────────────────────┐                                   │
│  │ Impuesto                │                                   │
│  │ [Seleccionar...    ▼]   │                                   │
│  └─────────────────────────┘                                   │
│                                                                 │
│  🖼️ Imagen                                                      │
│  ┌─────────────────────────────────────────────────────┐       │
│  │  [Seleccionar imagen...]                            │       │
│  └─────────────────────────────────────────────────────┘       │
│                                                                 │
│                              [Cancelar]  [💾 Guardar]           │
└─────────────────────────────────────────────────────────────────┘
```

### Product Child Form Modal

```
┌─────────────────────────────────────────────────────────────────┐
│  Nueva Variante - Acetaminofén                           [X]    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📦 Información de Variante                                     │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ Nombre Variante *       │ │ SKU                     │       │
│  │ [Caja x100_________]    │ │ [MED-001-100____]       │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ Código de Barras        │ │ Presentación            │       │
│  │ [7701234567890___]      │ │ [Caja x100        ▼]    │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│  💰 Precios                                                     │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ Precio Compra *         │ │ Precio Venta *          │       │
│  │ [$ 35.00__________]     │ │ [$ 45.00__________]     │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ ☑ Precio incluye IVA    │ │ Margen: 28.57%  ✓       │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│  📊 Inventario                                                  │
│  ┌─────────────────────────┐ ┌─────────────────────────┐       │
│  │ Stock Mínimo            │ │ Stock Máximo            │       │
│  │ [10________________]    │ │ [100_______________]    │       │
│  └─────────────────────────┘ └─────────────────────────┘       │
│                                                                 │
│                              [Cancelar]  [💾 Guardar]           │
└─────────────────────────────────────────────────────────────────┘
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Parent Product CRUD Integrity
*For any* valid product data, creating a parent product should store all required fields (name, category_id, unit_id) and optional fields correctly, and editing should persist changes accurately.
**Validates: Requirements 1.1, 1.2**

### Property 2: SKU Auto-Generation
*For any* parent product created without a SKU, the system should automatically generate a unique SKU.
**Validates: Requirements 1.5**

### Property 3: Parent Deletion Protection
*For any* parent product with active children, attempting to delete it should fail and return an error.
**Validates: Requirements 1.4**

### Property 4: Child Product Inheritance
*For any* child product created, it should inherit category_id, subcategory_id, brand_id, and tax_id from its parent product.
**Validates: Requirements 2.1, 2.2**

### Property 5: Child Product Optional Fields
*For any* child product, it should accept optional fields (presentation_id, color_id, product_model_id, size, weight, imei) based on field configuration.
**Validates: Requirements 2.4**

### Property 6: Parent-Child Cascade Delete
*For any* parent product deletion (when allowed), all associated child products should be deleted or deactivated.
**Validates: Requirements 2.5**

### Property 7: Price Margin Calculation
*For any* child product with purchase_price and sale_price, the margin should be calculated as ((sale_price - purchase_price) / purchase_price) * 100.
**Validates: Requirements 3.1, 3.2**

### Property 8: Stock Level Detection
*For any* child product where current_stock <= min_stock, the system should flag it as low stock.
**Validates: Requirements 4.1, 4.2, 4.3**

### Property 9: Field Configuration Application
*For any* field marked as hidden in ProductFieldSetting, that field should not be included in form validation as required.
**Validates: Requirements 5.1, 5.2, 5.4**

### Property 10: Parent Deactivation Cascade
*For any* parent product that is deactivated, all its child products should also be deactivated.
**Validates: Requirements 7.1, 7.2**

### Property 11: Inactive Product Search Exclusion
*For any* search query with active-only filter, inactive products (parent or child) should not appear in results.
**Validates: Requirements 7.3, 8.1, 8.2**

### Property 12: Image Fallback Resolution
*For any* child product without an image, when requesting its display image, the parent's image should be returned.
**Validates: Requirements 6.1, 6.2, 6.3**

### Property 13: Activity Log Creation
*For any* product create, update, or delete operation, an activity log entry should be created with user_id, action type, and timestamp.
**Validates: Requirements 9.1, 9.2**

## Error Handling

| Error Scenario | Response | User Message |
|----------------|----------|--------------|
| Parent deletion with active children | Prevent deletion | "No se puede eliminar: tiene variantes activas" |
| Duplicate SKU | Validation error | "El SKU ya está registrado" |
| Duplicate barcode | Validation error | "El código de barras ya existe" |
| Sale price < Purchase price | Warning (allow save) | "Advertencia: precio de venta menor al costo" |
| Required field missing | Validation error | "El campo {field} es obligatorio" |
| Invalid image format | Validation error | "Formato de imagen no válido (jpg, png, webp)" |
| Image too large | Validation error | "La imagen no debe superar 2MB" |
| Category not found | Validation error | "La categoría seleccionada no existe" |

## Testing Strategy

### Unit Tests
- Model relationships (Product hasMany ProductChild)
- SKU auto-generation logic
- Margin calculation helper
- Low stock detection logic
- Image fallback resolution
- Field configuration loading

### Property-Based Tests
Using PHPUnit with data providers for property testing:

1. **Property 1-2**: Test CRUD operations with random valid data
2. **Property 3**: Test deletion protection with various child states
3. **Property 4-5**: Test inheritance and optional fields
4. **Property 6**: Test cascade behaviors
5. **Property 7**: Test margin calculation with edge cases (zero prices, equal prices)
6. **Property 8**: Test stock level detection
7. **Property 9**: Test field configuration application
8. **Property 10-11**: Test status cascades and search filtering
9. **Property 12**: Test image resolution logic
10. **Property 13**: Test activity logging

### Integration Tests
- Full product creation flow (parent + children)
- Search and filter functionality
- Field configuration changes affecting forms
- Image upload and storage
- Permission-based access control

