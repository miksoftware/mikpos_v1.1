# Sistema de Habilitación / Deshabilitación del Sistema

## Descripción

Permite habilitar o deshabilitar todo el sistema MikPOS a través de un endpoint HTTP. Cuando el sistema está **deshabilitado**, cualquier visita a cualquier ruta del sistema (POS, tienda, login, etc.) muestra una página de mantenimiento con el mensaje:

> *Lo sentimos, estamos trabajando para mejorar. Comuníquese con soporte técnico.*

El sistema vuelve a operar con normalidad en cuanto se habilita nuevamente.

---

## Configuración inicial

Agregar la siguiente variable al archivo `.env` del proyecto:

```env
SYSTEM_ADMIN_TOKEN=tu-token-secreto-aqui
```

> **Importante:** Usa un token largo y aleatorio. Este token es la única protección del endpoint. Nunca lo expongas públicamente.

---

## Endpoints

Base URL: `https://tu-dominio.com`

---

### 1. Alternar estado (toggle)

**`POST /api/system/toggle`**

Cambia el estado del sistema al opuesto del actual, o fuerza un estado específico mediante el campo `action`.

#### Headers

| Header         | Valor              |
|----------------|--------------------|
| `Content-Type` | `application/json` |
| `Accept`       | `application/json` |

#### Body (JSON)

| Campo    | Tipo   | Requerido | Descripción                                         |
|----------|--------|-----------|-----------------------------------------------------|
| `token`  | string | ✅        | Token configurado en `SYSTEM_ADMIN_TOKEN`           |
| `action` | string | ❌        | `"enable"` o `"disable"`. Omitir para auto-toggle.  |

#### Ejemplos

**Toggle automático:**
```bash
curl -X POST https://tu-dominio.com/api/system/toggle \
  -H "Content-Type: application/json" \
  -d '{"token": "tu-token-secreto-aqui"}'
```

**Deshabilitar el sistema:**
```bash
curl -X POST https://tu-dominio.com/api/system/toggle \
  -H "Content-Type: application/json" \
  -d '{"token": "tu-token-secreto-aqui", "action": "disable"}'
```

**Habilitar el sistema:**
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

**401 Unauthorized — Token incorrecto o ausente:**
```json
{
  "success": false,
  "message": "No autorizado."
}
```

---

### 2. Consultar estado actual

**`GET /api/system/status`**

Retorna el estado actual del sistema sin modificarlo.

#### Query Parameters

| Parámetro | Tipo   | Requerido | Descripción                             |
|-----------|--------|-----------|-----------------------------------------|
| `token`   | string | ✅        | Token configurado en `SYSTEM_ADMIN_TOKEN` |

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

| Elemento | Descripción |
|----------|-------------|
| **Archivo de bloqueo** | `storage/system.disabled` — su existencia indica que el sistema está deshabilitado. |
| **Middleware** | `App\Http\Middleware\CheckSystemStatus` — se ejecuta globalmente en cada request. Si el archivo existe, retorna HTTP 503 con la vista `maintenance`. |
| **Vista** | `resources/views/maintenance.blade.php` — página de mantenimiento moderna, sin assets externos ni dependencias de Vite. |
| **Controlador** | `App\Http\Controllers\SystemToggleController` — gestiona la creación/eliminación del archivo de bloqueo. |

---

## Notas de seguridad

- El token se valida comparando el valor enviado contra `SYSTEM_ADMIN_TOKEN` en el `.env`. Si la variable no está definida, **todos los intentos serán rechazados**.
- Los endpoints `/api/system/toggle` y `/api/system/status` están **excluidos** de la verificación de mantenimiento, por lo que funcionan incluso cuando el sistema está deshabilitado.
- Se recomienda usar HTTPS y un token de al menos 32 caracteres aleatorios.

```bash
# Generar un token seguro con PHP
php -r "echo bin2hex(random_bytes(32));"
```
