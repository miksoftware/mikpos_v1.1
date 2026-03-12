# Documento de Requisitos — Módulo E-commerce

## Introducción

Módulo de tienda en línea (e-commerce) integrado nativamente con el sistema POS MikPOS existente. Permite a los clientes registrarse, navegar el catálogo de productos, agregar productos al carrito y realizar pedidos seleccionando métodos de pago ya configurados en el POS. Los pedidos generados quedan en estado "Pendiente de aprobación" hasta que un administrador del POS los apruebe o rechace. El stock se reserva inmediatamente al confirmar la compra y se devuelve automáticamente si el pedido es rechazado.

## Glosario

- **Tienda**: Aplicación web pública de e-commerce accesible desde una URL independiente del panel POS.
- **Cliente_Ecommerce**: Cliente registrado en la tienda en línea. Utiliza la misma tabla `customers` del POS con campos adicionales para autenticación (`password`, `email_verified_at`).
- **Catálogo**: Listado público de productos activos con stock disponible, consumidos directamente de la tabla `products` existente.
- **Carrito**: Colección temporal de productos seleccionados por el Cliente_Ecommerce antes de confirmar el pedido.
- **Pedido**: Registro de venta generado desde la Tienda, almacenado en la tabla `sales` con `source = 'ecommerce'` y estado inicial `pending_approval`.
- **Reserva_Stock**: Decremento inmediato del `current_stock` del producto al confirmar el Pedido, con movimiento de inventario registrado.
- **Devolución_Stock**: Incremento del `current_stock` del producto cuando un Pedido es rechazado por el administrador, con movimiento de inventario inverso.
- **Dirección_Envío**: Datos de envío proporcionados por el Cliente_Ecommerce durante el checkout, almacenados en la tabla `ecommerce_orders`.
- **Panel_POS**: Panel de administración interno de MikPOS, accesible solo por usuarios autenticados del sistema (admin, cajero, etc.).
- **Método_Pago**: Método de pago registrado y activo en la tabla `payment_methods` del POS.

## Requisitos

### Requisito 1: Aislamiento de Rutas y Layout

**Historia de Usuario:** Como propietario del negocio, quiero que la tienda en línea sea accesible desde una URL independiente del panel POS, para que los clientes no accedan al sistema administrativo.

#### Criterios de Aceptación

1. THE Tienda SHALL servir todas las páginas públicas bajo el prefijo de ruta `/shop` con un layout independiente (`layouts.shop`) separado del layout del Panel_POS (`layouts.app`).
2. THE Tienda SHALL utilizar un grupo de rutas con middleware propio (`ecommerce.auth`) independiente del guard de autenticación del Panel_POS (`auth`).
3. WHEN un Cliente_Ecommerce intenta acceder a una ruta del Panel_POS, THE Tienda SHALL redirigir al Cliente_Ecommerce a la página de login de la Tienda (`/shop/login`).
4. WHEN un usuario del Panel_POS intenta acceder a una ruta de la Tienda como cliente, THE Tienda SHALL requerir autenticación independiente con credenciales de Cliente_Ecommerce.

### Requisito 2: Registro y Autenticación de Clientes E-commerce

**Historia de Usuario:** Como visitante de la tienda, quiero registrarme con mis datos personales y luego iniciar sesión, para poder realizar compras y consultar mi historial de pedidos.

#### Criterios de Aceptación

1. THE Tienda SHALL presentar un formulario de registro con los campos: tipo de persona (`customer_type`), tipo de documento (`tax_document_id`), número de documento (`document_number`), nombre (`first_name`), apellido (`last_name`), razón social (`business_name` solo para tipo jurídico), teléfono (`phone`), correo electrónico (`email`), contraseña y confirmación de contraseña.
2. WHEN un visitante completa el formulario de registro con datos válidos, THE Tienda SHALL crear un registro en la tabla `customers` con los mismos campos y estructura que utiliza el módulo de clientes del POS, incluyendo `password` (hash bcrypt) y `branch_id` de la sucursal configurada para e-commerce.
3. THE Tienda SHALL validar que el correo electrónico sea único en la tabla `customers` y que el número de documento sea único por tipo de documento.
4. WHEN un Cliente_Ecommerce proporciona credenciales válidas (email y contraseña) en el formulario de login, THE Tienda SHALL autenticar al Cliente_Ecommerce usando un guard de Laravel independiente (`customer`) y redirigir a la página principal de la Tienda.
5. IF un Cliente_Ecommerce proporciona credenciales inválidas, THEN THE Tienda SHALL mostrar un mensaje de error indicando que las credenciales son incorrectas sin revelar si el email existe o no.
6. WHEN un Cliente_Ecommerce hace clic en "Cerrar sesión", THE Tienda SHALL invalidar la sesión del Cliente_Ecommerce y redirigir a la página de login de la Tienda.

### Requisito 3: Catálogo de Productos

**Historia de Usuario:** Como Cliente_Ecommerce, quiero navegar los productos disponibles en la tienda, para poder encontrar y seleccionar los que deseo comprar.

#### Criterios de Aceptación

1. THE Tienda SHALL mostrar únicamente productos de la tabla `products` que cumplan: `is_active = true`, `current_stock > 0`, y pertenezcan a la sucursal (`branch_id`) configurada para e-commerce.
2. THE Tienda SHALL mostrar para cada producto: imagen (o placeholder), nombre, precio de venta con impuesto, categoría, marca y stock disponible.
3. WHEN un Cliente_Ecommerce ingresa un término de búsqueda, THE Tienda SHALL filtrar productos por nombre, SKU o descripción en tiempo real.
4. THE Tienda SHALL permitir filtrar productos por categoría y por marca.
5. WHEN un producto tiene variantes activas (`product_children` con `is_active = true`), THE Tienda SHALL mostrar las variantes disponibles con su stock individual en la página de detalle del producto.
6. THE Tienda SHALL mostrar una página de detalle por producto con: imagen, nombre, descripción, precio con impuesto, stock disponible, categoría, marca, unidad de medida y selector de cantidad.

### Requisito 4: Carrito de Compras

**Historia de Usuario:** Como Cliente_Ecommerce, quiero agregar productos al carrito y gestionar las cantidades antes de confirmar mi pedido.

#### Criterios de Aceptación

1. WHEN un Cliente_Ecommerce autenticado hace clic en "Agregar al carrito" en un producto, THE Tienda SHALL agregar el producto al Carrito del Cliente_Ecommerce con cantidad 1 y almacenar el Carrito en la sesión del Cliente_Ecommerce.
2. IF un Cliente_Ecommerce intenta agregar al Carrito una cantidad que excede el `current_stock` del producto, THEN THE Tienda SHALL mostrar un mensaje indicando el stock máximo disponible y limitar la cantidad al stock disponible.
3. WHEN un Cliente_Ecommerce modifica la cantidad de un producto en el Carrito, THE Tienda SHALL actualizar el subtotal y total del Carrito en tiempo real.
4. WHEN un Cliente_Ecommerce elimina un producto del Carrito, THE Tienda SHALL remover el producto y recalcular los totales del Carrito.
5. THE Tienda SHALL mostrar en el Carrito: nombre del producto, precio unitario con impuesto, cantidad, subtotal por línea, total de impuestos y total general.
6. THE Tienda SHALL mostrar un indicador visual con la cantidad de productos en el Carrito en el encabezado de la Tienda.

### Requisito 5: Proceso de Checkout

**Historia de Usuario:** Como Cliente_Ecommerce, quiero completar mi compra seleccionando un método de pago y proporcionando mi dirección de envío, para que el negocio procese mi pedido.

#### Criterios de Aceptación

1. WHEN un Cliente_Ecommerce accede al checkout, THE Tienda SHALL mostrar el resumen del Carrito, los datos del Cliente_Ecommerce precargados y un formulario de dirección de envío (departamento, municipio, dirección, teléfono de contacto, notas).
2. THE Tienda SHALL mostrar únicamente los métodos de pago de la tabla `payment_methods` donde `is_active = true` para que el Cliente_Ecommerce seleccione uno.
3. WHEN un Cliente_Ecommerce confirma el pedido con datos válidos, THE Tienda SHALL crear un registro en la tabla `sales` con `status = 'pending_approval'`, `source = 'ecommerce'`, `payment_type = 'cash'` y los datos del Carrito como `sale_items`.
4. WHEN un Cliente_Ecommerce confirma el pedido, THE Tienda SHALL crear un registro en la tabla `ecommerce_orders` con la dirección de envío, notas del cliente y referencia al `sale_id`.
5. WHEN un Cliente_Ecommerce confirma el pedido, THE Tienda SHALL crear un registro en `sale_payments` con el método de pago seleccionado y el monto total.
6. IF el Carrito está vacío al intentar acceder al checkout, THEN THE Tienda SHALL redirigir al Cliente_Ecommerce al catálogo con un mensaje indicando que el Carrito está vacío.
7. WHEN el pedido se crea exitosamente, THE Tienda SHALL vaciar el Carrito del Cliente_Ecommerce y redirigir a la página de confirmación del pedido mostrando el número de factura.

### Requisito 6: Reserva Inmediata de Stock

**Historia de Usuario:** Como propietario del negocio, quiero que el stock se reserve inmediatamente al confirmar un pedido e-commerce, para evitar sobreventa de productos.

#### Criterios de Aceptación

1. WHEN un Cliente_Ecommerce confirma un pedido, THE Tienda SHALL decrementar el `current_stock` de cada producto (o `product_child`) incluido en el pedido dentro de una transacción de base de datos.
2. WHEN un Cliente_Ecommerce confirma un pedido, THE Tienda SHALL crear un registro de `InventoryMovement` por cada producto con `movement_type = 'out'`, referencia al `Sale` y código de documento del sistema `ecommerce-sale`.
3. IF durante la confirmación del pedido el `current_stock` de algún producto es menor que la cantidad solicitada, THEN THE Tienda SHALL cancelar toda la transacción, mostrar un mensaje indicando qué productos no tienen stock suficiente y mantener el Carrito intacto.
4. THE Tienda SHALL ejecutar la creación del pedido, el decremento de stock y los movimientos de inventario dentro de una única transacción de base de datos para garantizar consistencia.

### Requisito 7: Historial de Pedidos del Cliente

**Historia de Usuario:** Como Cliente_Ecommerce, quiero consultar el historial de mis pedidos, para conocer el estado de mis compras y ver los motivos de rechazo si aplica.

#### Criterios de Aceptación

1. THE Tienda SHALL mostrar una página de "Mis Pedidos" con todos los pedidos del Cliente_Ecommerce autenticado, ordenados del más reciente al más antiguo.
2. THE Tienda SHALL mostrar para cada pedido: número de factura, fecha, total, estado (Pendiente de aprobación, Aprobado, Rechazado) y método de pago.
3. WHEN un Cliente_Ecommerce selecciona un pedido, THE Tienda SHALL mostrar el detalle con: productos, cantidades, precios, dirección de envío y datos de pago.
4. WHEN un pedido tiene estado "Rechazado", THE Tienda SHALL mostrar el motivo de rechazo registrado por el administrador.

### Requisito 8: Gestión de Pedidos E-commerce desde el POS

**Historia de Usuario:** Como administrador del POS, quiero ver y gestionar los pedidos generados desde la tienda en línea, para aprobar o rechazar pedidos según la disponibilidad y validez.

#### Criterios de Aceptación

1. THE Panel_POS SHALL agregar un filtro de origen ("POS" / "E-commerce" / "Todos") en el módulo de Ventas existente para distinguir pedidos según el campo `source`.
2. THE Panel_POS SHALL agregar la opción de filtrar por estado `pending_approval` en el filtro de estado del módulo de Ventas.
3. WHEN un administrador abre el detalle de un pedido con `source = 'ecommerce'` y `status = 'pending_approval'`, THE Panel_POS SHALL mostrar los datos del cliente, dirección de envío (de `ecommerce_orders`), productos, método de pago y los botones "Aprobar" y "Rechazar".
4. WHEN un administrador hace clic en "Aprobar", THE Panel_POS SHALL cambiar el `status` del pedido a `completed` y registrar la acción en el log de actividad.
5. WHEN un administrador hace clic en "Rechazar", THE Panel_POS SHALL mostrar un campo obligatorio para ingresar el motivo de rechazo (mínimo 10 caracteres).
6. WHEN un administrador confirma el rechazo con un motivo válido, THE Panel_POS SHALL cambiar el `status` del pedido a `rejected`, guardar el motivo en `ecommerce_orders.rejection_reason`, ejecutar la Devolución_Stock y registrar la acción en el log de actividad.
7. WHEN un pedido es rechazado, THE Panel_POS SHALL crear un registro de `InventoryMovement` por cada producto con `movement_type = 'in'` para devolver el stock reservado, dentro de una transacción de base de datos.

### Requisito 9: Migraciones de Base de Datos

**Historia de Usuario:** Como desarrollador, quiero que las nuevas tablas y columnas se creen mediante migraciones de Laravel, para mantener la integridad del esquema de base de datos.

#### Criterios de Aceptación

1. THE Sistema SHALL crear una migración que agregue las columnas `password` (nullable string), `email_verified_at` (nullable timestamp) y `remember_token` (nullable string) a la tabla `customers` para soportar autenticación de e-commerce.
2. THE Sistema SHALL crear una migración que agregue la columna `source` (string, default `'pos'`) a la tabla `sales` para distinguir el origen del pedido.
3. THE Sistema SHALL crear una migración para la tabla `ecommerce_orders` con los campos: `id`, `sale_id` (foreign key a `sales`), `customer_id` (foreign key a `customers`), `shipping_department_id` (foreign key nullable a `departments`), `shipping_municipality_id` (foreign key nullable a `municipalities`), `shipping_address`, `shipping_phone`, `customer_notes`, `rejection_reason` (nullable text), `timestamps`.
4. THE Sistema SHALL crear un `SystemDocument` con código `ecommerce-sale` y prefijo `ECM` para los movimientos de inventario de pedidos e-commerce.
5. THE Sistema SHALL crear un seeder que registre el módulo `ecommerce` con los permisos `ecommerce_orders.view`, `ecommerce_orders.approve` y `ecommerce_orders.reject` en el sistema de permisos existente.

### Requisito 10: Configuración de Sucursal E-commerce

**Historia de Usuario:** Como administrador del POS, quiero configurar qué sucursal alimenta la tienda en línea, para controlar qué inventario se muestra a los clientes.

#### Criterios de Aceptación

1. THE Panel_POS SHALL permitir configurar la sucursal de e-commerce mediante una variable de entorno `ECOMMERCE_BRANCH_ID` o un registro en la tabla de configuración.
2. THE Tienda SHALL utilizar la sucursal configurada para filtrar todos los productos mostrados en el catálogo por `branch_id`.
3. IF la sucursal de e-commerce no está configurada, THEN THE Tienda SHALL mostrar una página informativa indicando que la tienda no está disponible temporalmente.
