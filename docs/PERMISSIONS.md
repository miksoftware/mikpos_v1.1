# Guía de Permisos - MikPOS

## Estructura del Sistema de Permisos

El sistema de permisos está organizado en:
- **Módulos**: Agrupan permisos relacionados (ej: usuarios, sucursales, pos)
- **Permisos**: Acciones específicas dentro de cada módulo (ej: view, create, edit, delete)
- **Roles**: Conjuntos de permisos asignables a usuarios

## Cómo Crear un Nuevo Módulo con Permisos

### 1. Agregar al Seeder

Edita `database/seeders/RolesAndPermissionsSeeder.php` y agrega tu módulo al array `$modules`:

```php
[
    'name' => 'products',           // Identificador único (snake_case)
    'display_name' => 'Productos',  // Nombre visible en UI
    'icon' => 'box',                // Icono (referencia)
    'order' => 8,                   // Orden en el menú
    'permissions' => [
        ['name' => 'products.view', 'display_name' => 'Ver Productos'],
        ['name' => 'products.create', 'display_name' => 'Crear Productos'],
        ['name' => 'products.edit', 'display_name' => 'Editar Productos'],
        ['name' => 'products.delete', 'display_name' => 'Eliminar Productos'],
        ['name' => 'products.import', 'display_name' => 'Importar Productos'],
        ['name' => 'products.export', 'display_name' => 'Exportar Productos'],
    ],
],
```

### 2. Convención de Nombres

Los permisos siguen el formato: `modulo.accion`

Acciones comunes:
- `view` - Ver/listar registros
- `create` - Crear nuevos registros
- `edit` - Editar registros existentes
- `delete` - Eliminar/desactivar registros
- `export` - Exportar datos
- `import` - Importar datos

### 3. Ejecutar Migración

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

O para ambiente de desarrollo (borra todo):
```bash
php artisan migrate:fresh --seed
```

### 4. Asignar Permisos a Roles Existentes

En el mismo seeder, busca la sección donde se asignan permisos a roles y agrega los nuevos:

```php
// Para branch_admin
$branchAdminPermissions = Permission::whereNotIn('name', [
    'roles.create', 'roles.edit', 'roles.delete',
    'activity_logs.export',
    // Agregar permisos que NO debe tener
])->pluck('id');
```

## Verificar Permisos en Código

### En Controladores/Livewire

```php
// Verificar un permiso específico
if (auth()->user()->hasPermission('products.create')) {
    // Puede crear productos
}

// Verificar si es super admin (tiene todos los permisos)
if (auth()->user()->isSuperAdmin()) {
    // Acceso total
}
```

### En Vistas Blade

```blade
@if(auth()->user()->hasPermission('products.create'))
    <button>Crear Producto</button>
@endif
```

### Middleware (Opcional)

Puedes crear un middleware para proteger rutas:

```php
// app/Http/Middleware/CheckPermission.php
public function handle($request, Closure $next, $permission)
{
    if (!auth()->user()->hasPermission($permission)) {
        abort(403, 'No tienes permiso para realizar esta acción.');
    }
    return $next($request);
}

// Uso en rutas
Route::get('/products', ProductsController::class)->middleware('permission:products.view');
```

## Permisos Actuales del Sistema

| Módulo | Permisos |
|--------|----------|
| dashboard | view |
| branches | view, create, edit, delete |
| users | view, create, edit, delete |
| roles | view, create, edit, delete, assign |
| pos | access, sell, discount, cancel, reprint |
| reports | sales, inventory, users, export |
| activity_logs | view, export |

## Roles del Sistema

| Rol | Descripción | Editable |
|-----|-------------|----------|
| super_admin | Acceso total a todo el sistema | No |
| branch_admin | Administración de una sucursal | No |
| supervisor | Supervisión y reportes | No |
| cashier | Operaciones básicas de POS | No |

Los roles marcados como "Sistema" no pueden eliminarse pero sí editarse sus permisos (excepto super_admin).
