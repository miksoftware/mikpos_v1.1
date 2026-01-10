# Implementation Plan: Products Module

## Overview

Implementación del módulo de productos con sistema jerárquico padre-hijo y campos configurables por tipo de negocio. Se utilizará PHP/Laravel 12, Livewire 3.x y el patrón de diseño existente en MikPOS.

## Tasks

- [x] 1. Crear migraciones de base de datos
  - [x] 1.1 Crear migración para tabla `products` (productos padre)
    - Campos: id, sku, name, description, category_id, subcategory_id, brand_id, unit_id, tax_id, image, purchase_price, sale_price, price_includes_tax, min_stock, max_stock, current_stock, is_active
    - Foreign keys a categories, subcategories, brands, units, taxes
    - Índices en sku (unique), name, category_id, brand_id
    - Migración adicional: `2026_01_10_060000_add_pricing_and_stock_to_products_table.php`
    - _Requirements: 1.1, 1.5, 3.1, 4.1_
  - [x] 1.2 Crear migración para tabla `product_children` (variantes)
    - Campos: id, product_id, sku, barcode, name, presentation_id, color_id, product_model_id, size, weight, purchase_price, sale_price, price_includes_tax, min_stock, max_stock, current_stock, image, imei, is_active
    - Foreign keys a products, presentations, colors, product_models
    - Índices en sku (unique), barcode (unique), product_id
    - _Requirements: 2.1, 3.1, 4.1_
  - [x] 1.3 Crear migración para tabla `product_field_settings` (configuración de campos)
    - Campos: id, branch_id, field_name, is_visible, is_required, display_order
    - Foreign key a branches (nullable para config global)
    - _Requirements: 5.1, 5.3_

- [x] 2. Crear modelos Eloquent
  - [x] 2.1 Crear modelo Product con relaciones y métodos
    - Relaciones: category, subcategory, brand, unit, tax, children
    - Método: generateSku() para auto-generación (corregido para MySQL)
    - Método: canDelete() para verificar si tiene hijos activos
    - Método: getMargin() para calcular margen de ganancia
    - Método: hasNegativeMargin() para detectar margen negativo
    - Método: isLowStock() para detectar stock bajo
    - Scope: active() para filtrar activos
    - _Requirements: 1.1, 1.4, 1.5, 3.2, 4.3_
  - [x] 2.2 Crear modelo ProductChild con relaciones y métodos
    - Relaciones: product (parent), presentation, color, productModel
    - Método: getMargin() para calcular margen de ganancia
    - Método: isLowStock() para detectar stock bajo
    - Método: getDisplayImage() para resolver imagen (fallback a padre)
    - Accessor: inherited fields from parent
    - _Requirements: 2.1, 2.2, 3.2, 4.3, 6.3_
  - [x] 2.3 Crear modelo ProductFieldSetting con métodos
    - Método estático: getFieldsForBranch($branchId)
    - Método estático: applyPreset($preset, $branchId)
    - Constantes: CONFIGURABLE_FIELDS, PRESETS
    - _Requirements: 5.1, 5.2, 5.5_
  - [x] 2.4 Escribir tests unitarios para modelos

    - Test relaciones Product-ProductChild
    - Test generateSku()
    - Test getMargin() con diferentes precios
    - Test isLowStock() con diferentes niveles
    - Test getDisplayImage() fallback
    - **Property 2: SKU Auto-Generation**
    - **Property 7: Price Margin Calculation**
    - **Property 8: Stock Level Detection**
    - **Property 12: Image Fallback Resolution**
    - _Requirements: 1.5, 3.2, 4.3, 6.3_

- [x] 3. Checkpoint - Verificar modelos y migraciones
  - Ejecutar migraciones: `php artisan migrate`
  - Ejecutar tests unitarios de modelos
  - Verificar relaciones en tinker

- [x] 4. Crear componente Livewire Products (listado y CRUD padre)
  - [x] 4.1 Crear componente Products.php
    - Propiedades: search, filters (category, brand, status), pagination
    - Métodos: render(), create(), edit(), store(), confirmDelete(), delete(), toggleStatus()
    - Lógica de búsqueda en name, sku, description
    - Filtros por categoría, marca, estado
    - _Requirements: 1.1, 1.2, 1.3, 7.1, 8.1, 8.2_
  - [x] 4.2 Crear vista products.blade.php
    - Tabla con productos padre y expansión de hijos
    - Columnas: Producto, Categoría, Precio (con margen), Stock, Variantes, Estado, Acciones
    - Barra de búsqueda y filtros
    - Modal de crear/editar producto padre con precios, stock y cálculo de margen en tiempo real
    - Modal de confirmación de eliminación
    - Indicadores visuales de estado, margen negativo y stock bajo
    - _Requirements: 1.3, 3.2, 3.4, 4.3, 7.4, 8.3_
  - [x] 4.3 Implementar lógica de eliminación protegida
    - Verificar si tiene hijos activos antes de eliminar
    - Mostrar mensaje de error si no se puede eliminar
    - _Requirements: 1.4_
  - [x] 4.4 Escribir tests de feature para Products

    - Test crear producto padre
    - Test editar producto padre
    - Test eliminar producto sin hijos
    - Test protección eliminación con hijos
    - Test búsqueda y filtros
    - Test toggle status
    - **Property 1: Parent Product CRUD Integrity**
    - **Property 3: Parent Deletion Protection**
    - _Requirements: 1.1, 1.2, 1.4, 8.1, 8.2_

- [x] 5. Implementar gestión de productos hijo (variantes)
  - [x] 5.1 Agregar métodos de hijo al componente Products
    - Métodos: createChild(), editChild(), storeChild(), deleteChild(), toggleChildStatus()
    - Cargar configuración de campos visibles/requeridos
    - Validación dinámica según configuración
    - _Requirements: 2.1, 2.4, 5.2_
  - [x] 5.2 Agregar modal de producto hijo a la vista
    - Formulario con campos configurables
    - Cálculo de margen en tiempo real (Alpine.js)
    - Advertencia visual si precio venta < precio compra
    - Campos heredados del padre (solo lectura)
    - _Requirements: 2.1, 2.2, 3.2, 3.4_
  - [x] 5.3 Implementar herencia de campos del padre
    - Al crear hijo, copiar category_id, subcategory_id, brand_id, tax_id
    - Mostrar estos campos como información heredada
    - _Requirements: 2.2_
  - [x] 5.4 Implementar cascada de desactivación
    - Al desactivar padre, desactivar todos los hijos
    - Al eliminar padre (si permitido), eliminar hijos
    - _Requirements: 2.5, 7.2_
  - [x] 5.5 Escribir tests para productos hijo

    - Test crear producto hijo
    - Test herencia de campos
    - Test campos opcionales
    - Test cascada desactivación
    - Test cascada eliminación
    - **Property 4: Child Product Inheritance**
    - **Property 5: Child Product Optional Fields**
    - **Property 6: Parent-Child Cascade Delete**
    - **Property 10: Parent Deactivation Cascade**
    - _Requirements: 2.1, 2.2, 2.4, 2.5, 7.2_

- [x] 6. Checkpoint - Verificar CRUD completo
  - Probar crear producto padre con todos los campos
  - Probar crear variantes con diferentes combinaciones
  - Probar cascadas de desactivación/eliminación
  - Ejecutar todos los tests

- [x] 7. Crear componente de configuración de campos
  - [x] 7.1 Crear componente ProductFieldConfig.php
    - Cargar configuración actual de campos
    - Métodos: saveSettings(), applyPreset()
    - Lista de presets disponibles
    - _Requirements: 5.1, 5.2, 5.5_
  - [x] 7.2 Crear vista product-field-config.blade.php
    - Lista de campos con toggles visible/requerido
    - Selector de preset de negocio
    - Botón guardar configuración
    - _Requirements: 5.1, 5.4, 5.5_
  - [x] 7.3 Escribir tests para configuración de campos

    - Test guardar configuración
    - Test aplicar preset
    - Test campos ocultos no aparecen en formulario
    - **Property 9: Field Configuration Application**
    - _Requirements: 5.1, 5.2, 5.4_

- [x] 8. Implementar gestión de imágenes
  - [x] 8.1 Agregar upload de imagen a productos padre
    - Validar formato (jpg, png, webp) y tamaño (max 2MB)
    - Almacenar en storage/app/public/products
    - Generar thumbnail opcional
    - _Requirements: 6.1, 6.4_
  - [x] 8.2 Agregar upload de imagen a productos hijo
    - Misma validación que padre
    - Almacenar en storage/app/public/products/variants
    - _Requirements: 6.2, 6.4_
  - [x] 8.3 Implementar resolución de imagen con fallback
    - Si hijo no tiene imagen, mostrar imagen del padre
    - Si padre no tiene imagen, mostrar placeholder
    - _Requirements: 6.3_

- [x] 9. Implementar búsqueda y filtros avanzados
  - [x] 9.1 Mejorar búsqueda para incluir hijos
    - Buscar en nombre, SKU, código de barras de padre e hijos
    - Mostrar padre cuando un hijo coincide
    - _Requirements: 8.1_
  - [x] 9.2 Agregar filtro de stock bajo
    - Filtrar productos con stock <= min_stock
    - Indicador visual en listado
    - _Requirements: 4.3_
  - [x] 9.3 Implementar exclusión de inactivos en búsqueda POS
    - Scope para excluir productos inactivos
    - Aplicar en búsquedas de POS
    - _Requirements: 7.3_
  - [x] 9.4 Escribir tests de búsqueda y filtros

    - Test búsqueda por diferentes campos
    - Test filtros combinados
    - Test exclusión de inactivos
    - **Property 11: Inactive Product Search Exclusion**
    - _Requirements: 7.3, 8.1, 8.2_

- [x] 10. Integrar Activity Logging
  - [x] 10.1 Agregar logging a operaciones de producto padre
    - Log en create, update, delete
    - Incluir cambios realizados
    - _Requirements: 9.1, 9.2_
  - [x] 10.2 Agregar logging a operaciones de producto hijo
    - Log en create, update, delete de variantes
    - Incluir referencia al padre
    - _Requirements: 9.1, 9.2_
  - [x] 10.3 Escribir tests de activity logging

    - Test log en crear producto
    - Test log en editar producto
    - Test log en eliminar producto
    - **Property 13: Activity Log Creation**
    - _Requirements: 9.1, 9.2_

- [x] 11. Configurar rutas y permisos
  - [x] 11.1 Agregar rutas en web.php
    - /products - listado y CRUD
    - /product-field-config - configuración de campos
    - Middleware de autenticación y permisos
    - _Requirements: 1.1_
  - [x] 11.2 Crear seeder de permisos
    - products.view, products.create, products.edit, products.delete
    - product_field_config.view, product_field_config.edit
    - Asignar a roles existentes
    - _Requirements: 1.1_
  - [x] 11.3 Agregar items al menú de navegación
    - Productos en sección "Creación"
    - Configuración de campos en "Administración > Configuración"
    - _Requirements: 1.1_

- [x] 12. Checkpoint final - Verificar módulo completo
  - Ejecutar suite completa de tests
  - Probar flujo completo en navegador
  - Verificar permisos funcionan correctamente
  - Verificar activity logs se crean correctamente

## Notes

- Tasks marcadas con `*` son opcionales (tests) y pueden omitirse para MVP más rápido
- Cada task referencia los requerimientos específicos que implementa
- Los checkpoints permiten validación incremental
- Property tests validan propiedades de correctitud del diseño
- Se usa el patrón existente de Livewire components de MikPOS
- ActivityLogService existente se reutiliza para logging
