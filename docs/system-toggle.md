# Sistema de Habilitación / Deshabilitación del Sistema

## Descripción

Permite habilitar o deshabilitar todo el sistema MikPOS a través de dos endpoints HTTP. Cuando el sistema está **deshabilitado**, cualquier visita a cualquier ruta (POS, tienda, login, dashboard, etc.) retorna HTTP 503 y muestra una página de mantenimiento con el mensaje:

> *Este sistema ha sido suspendido. El acceso a esta plataforma se encuentra suspendido. Para mayor información, comuníquese con soporte.*

El sistema vuelve a operar con normalidad en cuanto se habilita nuevamente.

---

## Configuración inicial

Agregar la siguiente variable al archivo `.env` del proyecto:

```env
SYSTEM_ADMIN_TOKEN=tu-token-secreto-aqui
```

El valor es leído internamente a través de `config('app.system_admin_token')`, definido en `config/app.php` como:

```php
'system_admin_token' => env('SYSTEM_ADMIN_TOKEN'),
```

> **Importante:** Usa un token largo y aleatorio. Si la variable no está definida en el `.env`, **todos los intentos serán rechazados automáticamente**. Nunca expongas el token públicamente.

---

## Endpoints

Base URL: `https://tu-dominio.com`

Ambos endpoints están **excluidos del middleware de mantenimiento** y de la **verificación CSRF**, por lo que funcionan siempre, incluso cuando el sistema está deshabilitado.

---

### 1. Alternar estado (toggle)

**`POST /api/system/toggle`**  
Nombre de ruta: `system.toggle`

Cambia el estado del sistema. Si no se especifica `action`, alterna automáticamente al estado opuesto del actual. Si se especifica `action`, fuerza ese estado sin importar el estado actual.

#### Headers

| Header         | Valor              |
|----------------|--------------------|
| `Content-Type` | `application/json` |
| `Accept`       | `application/json` |

#### Body (JSON)

| Campo    | Tipo   | Requerido | Descripción                                                                 |
|----------|--------|-----------|-----------------------------------------------------------------------------|
| `token`  | string | ✅        | Token configurado en `SYSTEM_ADMIN_TOKEN`                                   |
| `action` | string | ❌        | `"enable"` para habilitar, `"disable"` para deshabilitar. Omitir para auto-toggle. |

#### Lógica de ejecución

1. Se valida el `token`. Si es inválido o está ausente → `401 Unauthorized`.
2. Si `action = "disable"` → crea el archivo `storage/system.disabled` con la fecha/hora actual en ISO 8601 como contenido (solo si no existe ya).
3. Si `action = "enable"` → elimina el archivo `storage/system.disabled` (solo si existe).
4. Si `action` no se envía → verifica si el archivo existe: si existe lo elimina (habilita), si no existe lo crea (deshabilita).
5. Retorna el estado resultante.

#### Ejemplos

**Toggle automático (invierte el estado actual):**
```bash
curl -X POST https://tu-dominio.com/api/system/toggle \
  -H "Content-Type: application/json" \
  -d '{"token": "tu-token-secreto-aqui"}'
```

**Deshabilitar el sistema explícitamente:**
```bash
curl -X POST https://tu-dominio.com/api/system/toggle \
  -H "Content-Type: application/json" \
  -d '{"token": "tu-token-secreto-aqui", "action": "disable"}'
```

**Habilitar el sistema explícitamente:**
```bash
curl -X POST https://tu-dominio.com/api/system/toggle \
  -H "Content-Type: application/json" \
  -d '{"token": "tu-token-secreto-aqui", "action": "enable"}'
```

#### Respuestas

**200 OK — Sistema deshabilitado exitosamente:**
```json
{
  "success": true,
  "status": "disabled",
  "message": "Sistema deshabilitado correctamente."
}
```

**200 OK — Sistema habilitado exitosamente:**
```json
{
  "success": true,
  "status": "enabled",
  "message": "Sistema habilitado correctamente."
}
```

**401 Unauthorized — Token incorrecto, ausente o variable no definida en `.env`:**
```json
{
  "success": false,
  "message": "No autorizado."
}
```

---

### 2. Consultar estado actual

**`GET /api/system/status`**  
Nombre de ruta: `system.status`

Retorna el estado actual del sistema **sin modificarlo**. Solo lectura.

#### Query Parameters

| Parámetro | Tipo   | Requerido | Descripción                               |
|-----------|--------|-----------|-------------------------------------------|
| `token`   | string | ✅        | Token configurado en `SYSTEM_ADMIN_TOKEN` |

#### Lógica de ejecución

1. Se valida el `token`. Si es inválido o está ausente → `401 Unauthorized`.
2. Verifica si existe el archivo `storage/system.disabled`.
3. Retorna `"disabled"` si el archivo existe, `"enabled"` si no existe.

#### Ejemplo

```bash
curl "https://tu-dominio.com/api/system/status?token=tu-token-secreto-aqui"
```

#### Respuestas

**200 OK — Sistema habilitado:**
```json
{
  "success": true,
  "status": "enabled"
}
```

**200 OK — Sistema deshabilitado:**
```json
{
  "success": true,
  "status": "disabled"
}
```

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "No autorizado."
}
```

---

## Cómo funciona internamente

### Archivo de bloqueo

`storage/system.disabled` es el indicador de estado del sistema:

- **Su existencia** indica que el sistema está deshabilitado.
- **Su contenido** es la fecha y hora en formato ISO 8601 de cuando fue deshabilitado (ej. `2026-05-22T14:30:00+00:00`).
- **Su ausencia** indica que el sistema está habilitado y operativo.

### Middleware global

`App\Http\Middleware\CheckSystemStatus` está registrado de forma global en `bootstrap/app.php` (agregado al final del stack con `append`). Se ejecuta en **cada request** al sistema:

- Si `storage/system.disabled` existe → retorna HTTP 503 con la vista `maintenance`, excepto para las rutas `api/system/toggle` y `api/system/status`.
- Si el archivo no existe → deja pasar el request normalmente.

### Exclusiones de CSRF

Ambos endpoints están excluidos de la verificación de token CSRF en `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'api/system/toggle',
    'api/system/status',
]);
```

Esto permite llamarlos desde scripts externos o herramientas como `curl` sin necesidad de sesión web activa.

### Doble protección en rutas

Las rutas están definidas en `routes/web.php` con `withoutMiddleware` como capa adicional de protección:

```php
Route::prefix('api/system')
    ->withoutMiddleware(\App\Http\Middleware\CheckSystemStatus::class)
    ->group(function () {
        Route::post('/toggle', [SystemToggleController::class, 'toggle'])->name('system.toggle');
        Route::get('/status',  [SystemToggleController::class, 'status'])->name('system.status');
    });
```

Esto garantiza que los endpoints nunca queden bloqueados, incluso si la configuración del middleware global cambiara.

### Resumen de componentes

| Elemento | Archivo | Descripción |
|----------|---------|-------------|
| **Controlador** | `app/Http/Controllers/SystemToggleController.php` | Gestiona la creación/eliminación del archivo de bloqueo y retorna el estado JSON. |
| **Middleware** | `app/Http/Middleware/CheckSystemStatus.php` | Intercepta todos los requests; bloquea con HTTP 503 si el sistema está deshabilitado. |
| **Vista de mantenimiento** | `resources/views/maintenance.blade.php` | Página de mantenimiento sin dependencias de Vite ni assets externos. |
| **Archivo de bloqueo** | `storage/system.disabled` | Su existencia desactiva el sistema; su contenido es el timestamp de desactivación. |
| **Configuración** | `config/app.php` → clave `system_admin_token` | Lee `SYSTEM_ADMIN_TOKEN` del `.env`. |
| **Rutas** | `routes/web.php` | Define los dos endpoints con exclusión del middleware y nombres de ruta. |
| **Bootstrap** | `bootstrap/app.php` | Registra el middleware globalmente y excluye ambas rutas del CSRF. |

---

## Notas de seguridad

- El token se valida comparando el valor enviado contra `config('app.system_admin_token')`. Si la variable `SYSTEM_ADMIN_TOKEN` no está definida en el `.env`, el config retorna `null` y **todos los intentos serán rechazados**.
- Los endpoints `/api/system/toggle` y `/api/system/status` funcionan siempre, incluso con el sistema deshabilitado (doble exclusión: en el middleware y en la definición de rutas).
- Se recomienda usar HTTPS y un token de al menos 32 caracteres aleatorios.

```bash
# Generar un token seguro con PHP
php -r "echo bin2hex(random_bytes(32));"
```
