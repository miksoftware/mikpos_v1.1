# Debug Session: whatsapp-accepted-only

Status: [OPEN]

## Sintoma
- La API de Meta responde con `messages[].message_status = accepted`.
- En la app el registro queda en `accepted`.
- No llegan eventos posteriores `sent`, `delivered`, `read` o `failed`.
- El mensaje no llega al numero final en produccion.

## Hipotesis
1. El webhook no esta recibiendo eventos de produccion y por eso solo vemos la aceptacion inicial guardada por la API.
2. Meta acepta la solicitud pero retiene o descarta el mensaje despues por restricciones de plantilla, calidad, cuenta o modo de la app.
3. El numero destino no esta habilitado para el escenario actual de la cuenta y Meta no emite estados posteriores visibles.
4. La firma o el callback del webhook estan mal configurados y los POST reales de Meta se rechazan antes de persistirse.
5. El `phone_number_id`, token o plantilla corresponden a una configuracion valida para aceptar peticiones, pero no a un emisor realmente operativo en produccion.

## Evidencia Inicial
- UI muestra `accepted`.
- No hay `webhook_received_at`.
- No hay cambio de estado posterior en la trazabilidad.
- El archivo `.dbg/trae-debug-log-whatsapp-accepted-only.ndjson` aun no existe, por lo que no ha entrado ninguna llamada instrumentada del webhook ni del nuevo intento de prueba.
- El usuario reporta que la respuesta de API contiene `message_status = accepted`, pero sigue sin `sent`, `delivered`, `read` o `failed`.
- En la configuracion de Meta se observo el warning de app sin publicar y el listado de campos del webhook con `messages` inicialmente no suscrito.

## Plan
1. Instrumentar el flujo de envio y el webhook con puntos de depuracion.
2. Reproducir un envio de prueba o campana.
3. Comparar evidencia del webhook, respuesta API y trazas de runtime.
4. Confirmar o descartar hipotesis.
5. Aplicar correccion minima basada en evidencia.
