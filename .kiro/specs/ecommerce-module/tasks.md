# Plan de Implementación: Módulo E-commerce

## Visión General

Implementación incremental del módulo e-commerce para MikPOS. Se comienza con la base de datos y configuración, luego modelos y servicios, después los componentes de la tienda pública, y finalmente la integración con el panel POS existente. Cada paso construye sobre el anterior y se valida con tests.

## Tareas

- [x] 1. Migraciones de base de datos y configuración base
  - [x] 1.1 Crear migración para agregar campos de autenticación a la tabla `customers`
    - Crear migración `add_ecommerce_fields_to_customers_table` que agregue: `password` (nullable string después de `email`), `email_verified_at` (nullable timestamp después de `password`), `remember_token` (nullable string después de `email_verified_at`)
    - _Requisitos: 9.1_

  - [x] 1.2 Crear migración para agregar columna `source` a la tabla `sales`
    - Crear migración `add_source_to_sales_table` que agregue: `source` (string, default `'pos'`, después de `status`)
    - _Requisitos: 9.2_

  - [x] 1.3 Crear migración para la tabla `ecommerce_orders`
    - Crear migración `create_ecommerce_orders_table` con campos: `id`, `sale_id` (FK a `sales`, cascadeOnDelete), `customer_id` (FK a `customers`, cascadeOnDelete), `shipping_department_id` (FK nullable a `departments`, nullOnDelete), `shipping_municipality_id` (FK nullable a `municipalities`, nullOnDelete), `shipping_address` (string nullable), `shipping_phone` (string nullable), `customer_notes` (text nullable), `rejection_reason` (text nullable), `timestamps`
    - _Requisitos: 9.3_

  - [x] 1.4 Crear archivo de configuración `config/ecommerce.php`
    - Definir `branch_id` leyendo de `env('ECOMMERCE_BRANCH_ID')`
    - Agregar `ECOMMERCE_BRANCH_ID=1` al archivo `.env.example`
    - _Requisitos: 10.1_

  - [x] 1.5 Configurar guard `customer` en `config/auth.php`
    - Agregar guard `customer` con driver `session` y provider `customers`
    - Agregar provider `customers` con driver `eloquent` y modelo `App\Models\Customer`
    - _Requisitos: 1.2, 2.4_

  - [x] 1.6 Crear `EcommerceModuleSeeder`
    - Crear seeder en `database/seeders/EcommerceModuleSeeder.php`
    - Registrar módulo `ecommerce` con permisos: `ecommerce_orders.view`, `ecommerce_orders.approve`, `ecommerce_orders.reject`
    - Crear `SystemDocument` con code `ecommerce-sale`, prefix `ECM`, name `Venta E-commerce`
    - Agregar `EcommerceModuleSeeder` al array `$trackedSeeders` en `SeedPending.php` y `SeedMarkExecuted.php`
    - _Requisitos: 9.4, 9.5_

- [x] 2. Checkpoint - Ejecutar migraciones y seeder
  - Ejecutar `php artisan migrate` y verificar que las 3 migraciones se aplican correctamente
  - Ejecutar el seeder y verificar que el módulo, permisos y SystemDocument se crean
  - Preguntar al usuario si hay dudas

- [x] 3. Modelos y relaciones
  - [x] 3.1 Modificar modelo `Customer` para soportar autenticación
    - Cambiar la clase base de `Model` a `Illuminate\Foundation\Auth\User as Authenticatable`
    - Agregar `password`, `email_verified_at`, `remember_token` a `$fillable`
    - Agregar `$hidden` con `password` y `remember_token`
    - Agregar casts: `email_verified_at` → `datetime`, `password` → `hashed`
    - Agregar relación `ecommerceOrders(): HasMany` hacia `EcommerceOrder`
    - Agregar relación `sales(): HasMany` hacia `Sale` (si no existe)
    - _Requisitos: 2.2, 2.4_

  - [x] 3.2 Modificar modelo `Sale` con columna `source` y relaciones
    - Agregar `source` a `$fillable`
    - Agregar relación `ecommerceOrder(): HasOne` hacia `EcommerceOrder`
    - Agregar scope `scopeEcommerce(Builder $query)` que filtre por `source = 'ecommerce'`
    - Agregar scope `scopePendingApproval(Builder $query)` que filtre por `status = 'pending_approval'`
    - Agregar métodos helper: `isEcommerce(): bool` y `isPendingApproval(): bool`
    - _Requisitos: 8.1, 8.4, 9.2_

  - [x] 3.3 Crear modelo `EcommerceOrder`
    - Crear `app/Models/EcommerceOrder.php` con `$fillable`: `sale_id`, `customer_id`, `shipping_department_id`, `shipping_municipality_id`, `shipping_address`, `shipping_phone`, `customer_notes`, `rejection_reason`
    - Definir relaciones: `sale(): BelongsTo`, `customer(): BelongsTo`, `shippingDepartment(): BelongsTo`, `shippingMunicipality(): BelongsTo`
    - _Requisitos: 9.3, 5.4_

  - [ ]* 3.4 Escribir tests unitarios para modelos y relaciones
    - Verificar que `Customer` extiende `Authenticatable`
    - Verificar relaciones `Customer->ecommerceOrders`, `Sale->ecommerceOrder`, `EcommerceOrder->sale`
    - Verificar scopes `Sale::ecommerce()` y `Sale::pendingApproval()`
    - Verificar helpers `isEcommerce()` y `isPendingApproval()`
    - _Requisitos: 9.1, 9.2, 9.3_

- [x] 4. Middleware y rutas base
  - [x] 4.1 Crear middleware `EcommerceAuth`
    - Crear `app/Http/Middleware/EcommerceAuth.php`
    - Verificar autenticación con guard `customer` (`Auth::guard('customer')->check()`)
    - Redirigir a `/shop/login` si no autenticado
    - Registrar el middleware como alias `ecommerce.auth` en `bootstrap/app.php`
    - _Requisitos: 1.2, 1.3_

  - [x] 4.2 Crear layout `layouts.shop`
    - Crear `resources/views/layouts/shop.blade.php`
    - Layout independiente del POS con: header (logo, búsqueda, enlace carrito con badge, nombre cliente, logout), contenido principal, footer
    - Usar Tailwind CSS con paleta de colores MikPOS (`from-[#ff7261] to-[#a855f7]`)
    - Responsive con soporte móvil
    - Incluir verificación de `ECOMMERCE_BRANCH_ID` configurado: si no está configurado, mostrar página informativa "La tienda no está disponible temporalmente"
    - _Requisitos: 1.1, 4.6, 10.3_

  - [x] 4.3 Registrar rutas de e-commerce en `routes/web.php`
    - Grupo `guest:customer` bajo prefijo `/shop`: login y register
    - Grupo `ecommerce.auth` bajo prefijo `/shop`: catalog, product detail, cart, checkout, order confirmation, orders, logout
    - _Requisitos: 1.1, 1.2_

- [x] 5. Registro y autenticación de clientes
  - [x] 5.1 Crear componente `Shop\Auth\Register`
    - Crear `app/Livewire/Shop/Auth/Register.php` con `#[Layout('layouts.shop')]`
    - Formulario con campos: `customer_type` (natural/juridico), `tax_document_id`, `document_number`, `first_name`, `last_name`, `business_name` (solo jurídico), `phone`, `email`, `password`, `password_confirmation`
    - Validación: email único en `customers`, documento único por tipo, contraseña mínimo 8 caracteres
    - Al registrar: crear `Customer` con `branch_id = config('ecommerce.branch_id')`, password hasheado
    - Autenticar automáticamente con guard `customer` y redirigir a `/shop`
    - Crear vista `resources/views/livewire/shop/auth/register.blade.php`
    - _Requisitos: 2.1, 2.2, 2.3_

  - [x] 5.2 Crear componente `Shop\Auth\Login`
    - Crear `app/Livewire/Shop/Auth/Login.php` con `#[Layout('layouts.shop')]`
    - Formulario con campos: `email`, `password`
    - Autenticar con `Auth::guard('customer')->attempt()`
    - Mensaje genérico en error: "Las credenciales proporcionadas son incorrectas." sin revelar si el email existe
    - Redirigir a `/shop` al autenticar exitosamente
    - Crear vista `resources/views/livewire/shop/auth/login.blade.php`
    - _Requisitos: 2.4, 2.5_

  - [ ]* 5.3 Escribir tests de autenticación e-commerce
    - Test: registro con datos válidos crea cliente y autentica
    - Test: login con credenciales correctas autentica con guard `customer`
    - Test: logout invalida sesión y redirige a `/shop/login`
    - Test: credenciales inválidas muestran mensaje genérico
    - Test: guard `customer` aislado del guard `web`
    - **Propiedad 6: Registro crea cliente válido** — Valida Requisito 2.2
    - **Propiedad 7: Unicidad de email y documento en registro** — Valida Requisito 2.3
    - **Propiedad 8: Autenticación con credenciales válidas** — Valida Requisito 2.4
    - **Propiedad 9: Credenciales inválidas producen error genérico** — Valida Requisito 2.5
    - _Requisitos: 2.1-2.6, 1.3, 1.4_

- [x] 6. Checkpoint - Verificar autenticación
  - Asegurar que todas las pruebas pasan, preguntar al usuario si hay dudas
  - Verificar que el registro crea clientes correctamente
  - Verificar que el login/logout funciona con guard `customer`
  - Verificar aislamiento entre guards `web` y `customer`

- [x] 7. Catálogo de productos
  - [x] 7.1 Crear componente `Shop\Catalog`
    - Crear `app/Livewire/Shop/Catalog.php` con `#[Layout('layouts.shop')]`
    - Consultar productos donde `is_active = true`, `current_stock > 0`, `branch_id = config('ecommerce.branch_id')`
    - Implementar búsqueda en tiempo real por nombre, SKU o descripción
    - Implementar filtros por categoría y marca
    - Paginación de productos
    - Mostrar por producto: imagen (o placeholder), nombre, precio con impuesto, categoría, marca, stock disponible
    - Crear vista `resources/views/livewire/shop/catalog.blade.php` con grid responsive de tarjetas de producto
    - _Requisitos: 3.1, 3.2, 3.3, 3.4_

  - [x] 7.2 Crear componente `Shop\ProductDetail`
    - Crear `app/Livewire/Shop/ProductDetail.php` con `#[Layout('layouts.shop')]`
    - Recibir `Product $product` como parámetro de ruta
    - Validar que el producto pertenece a la sucursal e-commerce, está activo y tiene stock
    - Mostrar: imagen, nombre, descripción, precio con impuesto, stock disponible, categoría, marca, unidad de medida
    - Si el producto tiene variantes activas (`product_children` con `is_active = true`), mostrar selector de variantes con stock individual
    - Selector de cantidad con validación contra stock
    - Botón "Agregar al carrito"
    - Crear vista `resources/views/livewire/shop/product-detail.blade.php`
    - _Requisitos: 3.5, 3.6_

  - [ ]* 7.3 Escribir tests del catálogo
    - **Propiedad 1: Filtrado del catálogo por sucursal, estado y stock** — Valida Requisitos 3.1, 10.2
    - **Propiedad 2: Completitud de información del producto en catálogo** — Valida Requisito 3.2
    - **Propiedad 3: Búsqueda de productos por nombre, SKU o descripción** — Valida Requisito 3.3
    - **Propiedad 4: Filtrado por categoría y marca** — Valida Requisito 3.4
    - **Propiedad 5: Variantes activas en detalle de producto** — Valida Requisito 3.5
    - _Requisitos: 3.1-3.6, 10.2_

- [x] 8. Carrito de compras
  - [x] 8.1 Crear componente `Shop\Cart`
    - Crear `app/Livewire/Shop/Cart.php` con `#[Layout('layouts.shop')]`
    - Almacenar carrito en `session('ecommerce_cart')` con estructura: `items[]` con `product_id`, `product_child_id`, `name`, `sku`, `unit_price`, `tax_rate`, `quantity`, `max_stock`, `image`
    - Método `addToCart($productId, $productChildId = null)`: agregar producto con cantidad 1, validar stock
    - Método `updateQuantity($index, $quantity)`: actualizar cantidad, limitar a `current_stock`, recalcular totales
    - Método `removeItem($index)`: eliminar item y recalcular totales
    - Mostrar: nombre, precio unitario con impuesto, cantidad, subtotal por línea, total impuestos, total general
    - Botón "Ir al checkout"
    - Crear vista `resources/views/livewire/shop/cart.blade.php`
    - _Requisitos: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 8.2 Integrar badge del carrito en layout `shop`
    - Agregar indicador visual en el header del layout con la cantidad de items distintos en el carrito
    - Usar Livewire events o session para mantener el badge actualizado entre componentes
    - _Requisitos: 4.6_

  - [ ]* 8.3 Escribir tests del carrito
    - **Propiedad 10: Agregar producto al carrito** — Valida Requisito 4.1
    - **Propiedad 11: Límite de stock en carrito** — Valida Requisito 4.2
    - **Propiedad 12: Recálculo de totales del carrito** — Valida Requisitos 4.3, 4.4
    - **Propiedad 13: Badge del carrito refleja cantidad de items** — Valida Requisito 4.6
    - _Requisitos: 4.1-4.6_

- [x] 9. Servicio de checkout y proceso de compra
  - [x] 9.1 Crear `EcommerceCheckoutService`
    - Crear `app/Services/EcommerceCheckoutService.php`
    - Método `placeOrder(Customer $customer, array $cartItems, int $paymentMethodId, array $shippingData): Sale`
      - Dentro de `DB::transaction()`: validar stock, crear `Sale` con `status = 'pending_approval'`, `source = 'ecommerce'`, `payment_type = 'cash'`, crear `SaleItem` por cada item, crear `SalePayment`, crear `EcommerceOrder` con datos de envío, decrementar `current_stock`, crear `InventoryMovement` con `movement_type = 'out'` y `system_document.code = 'ecommerce-sale'`
      - Si stock insuficiente: lanzar excepción con detalle de productos sin stock, rollback automático
    - Método `approveOrder(Sale $sale): void` — cambiar status a `completed`, registrar ActivityLog
    - Método `rejectOrder(Sale $sale, string $reason): void`
      - Dentro de `DB::transaction()`: cambiar status a `rejected`, guardar `rejection_reason` en `ecommerce_orders`, incrementar `current_stock` por cada item, crear `InventoryMovement` con `movement_type = 'in'`, registrar ActivityLog
    - Métodos privados: `generateInvoiceNumber()`, `validateStock()`, `reserveStock()`, `returnStock()`
    - _Requisitos: 5.3, 5.4, 5.5, 6.1, 6.2, 6.3, 6.4, 8.4, 8.6, 8.7_

  - [x] 9.2 Crear componente `Shop\Checkout`
    - Crear `app/Livewire/Shop/Checkout.php` con `#[Layout('layouts.shop')]`
    - Mostrar resumen del carrito, datos del cliente precargados
    - Formulario de dirección de envío: departamento (searchable-select), municipio (filtrado por departamento), dirección, teléfono de contacto, notas
    - Mostrar métodos de pago activos (`PaymentMethod::where('is_active', true)`) para selección
    - Si carrito vacío: redirigir a `/shop` con mensaje
    - Al confirmar: llamar `EcommerceCheckoutService::placeOrder()`, vaciar carrito, redirigir a página de confirmación
    - Manejar errores de stock: mostrar mensaje con productos sin stock, mantener carrito
    - Crear vista `resources/views/livewire/shop/checkout.blade.php`
    - _Requisitos: 5.1, 5.2, 5.3, 5.6, 5.7_

  - [x] 9.3 Crear componente `Shop\OrderConfirmation`
    - Crear `app/Livewire/Shop/OrderConfirmation.php` con `#[Layout('layouts.shop')]`
    - Recibir `Sale $sale` como parámetro de ruta
    - Validar que la venta pertenece al cliente autenticado
    - Mostrar número de factura, resumen del pedido, mensaje de confirmación
    - Crear vista `resources/views/livewire/shop/order-confirmation.blade.php`
    - _Requisitos: 5.7_

  - [ ]* 9.4 Escribir tests del checkout y servicio
    - **Propiedad 14: Solo métodos de pago activos en checkout** — Valida Requisito 5.2
    - **Propiedad 15: Creación completa del pedido (round trip)** — Valida Requisitos 5.3, 5.4, 5.5
    - **Propiedad 16: Carrito vacío después de pedido exitoso** — Valida Requisito 5.7
    - **Propiedad 17: Reserva de stock al confirmar pedido** — Valida Requisitos 6.1, 6.2
    - **Propiedad 18: Atomicidad ante stock insuficiente** — Valida Requisitos 6.3, 6.4
    - Test unitario: checkout con carrito vacío redirige al catálogo
    - Test unitario: `EcommerceCheckoutService::placeOrder()` crea todos los registros correctamente
    - Test unitario: `EcommerceCheckoutService::placeOrder()` hace rollback si stock insuficiente
    - _Requisitos: 5.1-5.7, 6.1-6.4_

- [ ] 10. Checkpoint - Verificar flujo de compra completo
  - Asegurar que todas las pruebas pasan, preguntar al usuario si hay dudas
  - Verificar que el catálogo muestra productos correctos
  - Verificar que el carrito funciona correctamente
  - Verificar que el checkout crea pedidos con reserva de stock

- [x] 11. Historial de pedidos del cliente
  - [x] 11.1 Crear componente `Shop\Orders`
    - Crear `app/Livewire/Shop/Orders.php` con `#[Layout('layouts.shop')]`
    - Listar pedidos del cliente autenticado (`Auth::guard('customer')->user()`) ordenados por `created_at` descendente
    - Mostrar por pedido: número de factura, fecha, total, estado (Pendiente de aprobación / Aprobado / Rechazado), método de pago
    - Al seleccionar un pedido: mostrar detalle con productos, cantidades, precios, dirección de envío, datos de pago
    - Si el pedido está rechazado: mostrar motivo de rechazo desde `ecommerce_orders.rejection_reason`
    - Crear vista `resources/views/livewire/shop/orders.blade.php`
    - _Requisitos: 7.1, 7.2, 7.3, 7.4_

  - [ ]* 11.2 Escribir tests del historial de pedidos
    - **Propiedad 19: Pedidos del cliente ordenados por fecha descendente** — Valida Requisito 7.1
    - Test unitario: detalle de pedido rechazado muestra motivo
    - _Requisitos: 7.1-7.4_

- [x] 12. Gestión de pedidos e-commerce desde el POS
  - [x] 12.1 Modificar componente `Sales` para filtros de e-commerce
    - Agregar propiedad `$sourceFilter` con opciones: `'all'`, `'pos'`, `'ecommerce'`
    - Agregar filtro por `status = 'pending_approval'` en el dropdown de estado existente
    - Aplicar filtros en la query del `render()`: filtrar por `source` y por `status`
    - Actualizar vista `resources/views/livewire/sales.blade.php` con los nuevos selectores de filtro
    - _Requisitos: 8.1, 8.2_

  - [x] 12.2 Agregar funcionalidad de aprobar/rechazar pedidos en `Sales`
    - Agregar modal de detalle de pedido e-commerce que muestre: datos del cliente, dirección de envío (de `ecommerce_orders`), productos, método de pago
    - Agregar botones "Aprobar" y "Rechazar" visibles solo para pedidos con `source = 'ecommerce'` y `status = 'pending_approval'`
    - Método `approveOrder($saleId)`: llamar `EcommerceCheckoutService::approveOrder()`, verificar permiso `ecommerce_orders.approve`
    - Método `rejectOrder()`: mostrar campo de motivo (mínimo 10 caracteres), llamar `EcommerceCheckoutService::rejectOrder()`, verificar permiso `ecommerce_orders.reject`
    - Actualizar vista con modal de detalle e-commerce y modal de rechazo
    - _Requisitos: 8.3, 8.4, 8.5, 8.6, 8.7_

  - [ ]* 12.3 Escribir tests de gestión de pedidos desde POS
    - **Propiedad 20: Filtro de origen en módulo de Ventas** — Valida Requisito 8.1
    - **Propiedad 21: Aprobación cambia estado a completed** — Valida Requisito 8.4
    - **Propiedad 22: Validación del motivo de rechazo** — Valida Requisito 8.5
    - **Propiedad 23: Rechazo con devolución de stock** — Valida Requisitos 8.6, 8.7
    - Test unitario: filtro `pending_approval` en Sales
    - Test unitario: detalle de pedido e-commerce muestra botones aprobar/rechazar
    - _Requisitos: 8.1-8.7_

- [ ] 13. Checkpoint - Verificar integración completa
  - Asegurar que todas las pruebas pasan, preguntar al usuario si hay dudas
  - Verificar flujo completo: registro → catálogo → carrito → checkout → historial
  - Verificar flujo admin: filtrar pedidos → aprobar/rechazar → devolución de stock
  - Verificar aislamiento de guards y rutas

## Notas

- Las tareas marcadas con `*` son opcionales y pueden omitirse para un MVP más rápido
- Cada tarea referencia requisitos específicos para trazabilidad
- Los checkpoints aseguran validación incremental
- Los tests de propiedades validan propiedades universales de correctitud del diseño
- Los tests unitarios validan ejemplos específicos y edge cases
