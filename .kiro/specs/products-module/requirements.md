# Requirements Document

## Introduction

Sistema de gestión de productos para MikPOS que permite crear productos padre (base) y productos hijo (variantes/presentaciones). El sistema es flexible y configurable para adaptarse a diferentes tipos de negocios: farmacias, tiendas de celulares, calzado, ropa, joyerías, etc. Los administradores pueden configurar qué campos son relevantes para su tipo de negocio.

## Glossary

- **Product_System**: Sistema principal de gestión de productos
- **Parent_Product**: Producto base que puede tener múltiples variantes (ej: Acetaminofén)
- **Child_Product**: Variante o presentación de un producto padre (ej: Caja x12, Blister x5)
- **Product_Field_Config**: Configuración de campos visibles/requeridos por sucursal
- **SKU**: Stock Keeping Unit - código único de identificación del producto
- **Barcode**: Código de barras del producto

## Requirements

### Requirement 1: Gestión de Productos Padre

**User Story:** As a administrador, I want to crear productos padre (base), so that pueda definir productos principales que tendrán variantes.

#### Acceptance Criteria

1. WHEN un usuario crea un producto padre THEN THE Product_System SHALL almacenar los campos básicos: SKU, nombre, descripción, categoría, subcategoría, marca, unidad base, impuesto
2. WHEN un usuario edita un producto padre THEN THE Product_System SHALL actualizar los datos y reflejar cambios en productos hijo relacionados donde aplique
3. WHEN un usuario busca productos THEN THE Product_System SHALL permitir filtrar por nombre, SKU, categoría, marca y estado
4. WHEN un producto padre tiene hijos activos THEN THE Product_System SHALL prevenir su eliminación y mostrar advertencia
5. THE Product_System SHALL generar SKU automático si el usuario no proporciona uno

### Requirement 2: Gestión de Productos Hijo (Variantes)

**User Story:** As a administrador, I want to crear productos hijo vinculados a un padre, so that pueda manejar diferentes presentaciones del mismo producto.

#### Acceptance Criteria

1. WHEN un usuario crea un producto hijo THEN THE Product_System SHALL requerir selección de producto padre y permitir definir: nombre variante, SKU propio, código de barras, precio de compra, precio de venta, stock mínimo
2. WHEN un producto hijo se crea THEN THE Product_System SHALL heredar categoría, subcategoría, marca e impuesto del padre
3. WHEN un usuario lista productos hijo THEN THE Product_System SHALL mostrar información del padre junto con datos propios
4. THE Product_System SHALL permitir que un producto hijo tenga presentación, color y modelo específicos
5. WHEN se elimina un producto padre THEN THE Product_System SHALL eliminar o desactivar todos sus productos hijo

### Requirement 3: Precios y Costos

**User Story:** As a administrador, I want to gestionar precios de compra y venta, so that pueda controlar márgenes de ganancia.

#### Acceptance Criteria

1. THE Product_System SHALL almacenar precio de compra y precio de venta para cada producto hijo
2. WHEN un usuario ingresa precios THEN THE Product_System SHALL calcular y mostrar margen de ganancia en porcentaje
3. THE Product_System SHALL permitir definir precio con impuesto incluido o sin impuesto
4. WHEN el precio de venta es menor al precio de compra THEN THE Product_System SHALL mostrar advertencia visual

### Requirement 4: Control de Inventario Básico

**User Story:** As a administrador, I want to definir stock mínimo por producto, so that pueda recibir alertas de reabastecimiento.

#### Acceptance Criteria

1. THE Product_System SHALL permitir definir stock mínimo para cada producto hijo
2. THE Product_System SHALL permitir definir stock máximo opcional para cada producto hijo
3. WHEN el stock actual es menor o igual al stock mínimo THEN THE Product_System SHALL marcar visualmente el producto como "bajo stock"

### Requirement 5: Configuración de Campos por Sucursal

**User Story:** As a super administrador, I want to configurar qué campos son visibles y requeridos, so that cada sucursal pueda adaptar el sistema a su tipo de negocio.

#### Acceptance Criteria

1. THE Product_Field_Config SHALL permitir definir campos como: visible, oculto, requerido u opcional
2. WHEN un administrador configura campos THEN THE Product_System SHALL aplicar la configuración al formulario de productos
3. THE Product_Field_Config SHALL incluir campos configurables: modelo, presentación, color, código de barras, IMEI, talla, peso, dimensiones
4. WHEN un campo está marcado como oculto THEN THE Product_System SHALL no mostrar ese campo en formularios ni listados
5. THE Product_System SHALL proveer configuraciones predefinidas para tipos de negocio comunes (farmacia, celulares, ropa, etc.)

### Requirement 6: Gestión de Imágenes

**User Story:** As a administrador, I want to agregar imágenes a los productos, so that pueda identificarlos visualmente.

#### Acceptance Criteria

1. THE Product_System SHALL permitir subir una imagen principal por producto padre
2. THE Product_System SHALL permitir subir imagen opcional por producto hijo
3. WHEN no hay imagen de producto hijo THEN THE Product_System SHALL mostrar la imagen del padre
4. THE Product_System SHALL validar formato (jpg, png, webp) y tamaño máximo de imagen (2MB)

### Requirement 7: Estados y Activación

**User Story:** As a administrador, I want to activar/desactivar productos, so that pueda controlar qué productos están disponibles para venta.

#### Acceptance Criteria

1. THE Product_System SHALL permitir activar/desactivar productos padre e hijo independientemente
2. WHEN un producto padre se desactiva THEN THE Product_System SHALL desactivar automáticamente todos sus hijos
3. WHEN un producto hijo está desactivado THEN THE Product_System SHALL excluirlo de búsquedas en POS
4. THE Product_System SHALL mostrar indicador visual claro del estado activo/inactivo

### Requirement 8: Búsqueda y Filtrado

**User Story:** As a usuario, I want to buscar y filtrar productos eficientemente, so that pueda encontrar rápidamente lo que necesito.

#### Acceptance Criteria

1. WHEN un usuario busca THEN THE Product_System SHALL buscar en: nombre, SKU, código de barras, descripción
2. THE Product_System SHALL permitir filtrar por: categoría, subcategoría, marca, estado, con/sin stock
3. THE Product_System SHALL mostrar resultados paginados con 10 items por página
4. WHEN se aplican filtros THEN THE Product_System SHALL mantener los filtros durante la navegación

### Requirement 9: Registro de Actividad

**User Story:** As a administrador, I want to ver historial de cambios en productos, so that pueda auditar modificaciones.

#### Acceptance Criteria

1. WHEN un producto es creado, editado o eliminado THEN THE Product_System SHALL registrar la acción en el log de actividad
2. THE Product_System SHALL registrar usuario, fecha, hora y cambios realizados
3. THE Product_System SHALL usar el ActivityLogService existente para consistencia
