# tracking — Documentación técnica

Módulo PHP que permite a los destinatarios de paquetes de **pakAG** rastrear el estado de su envío en tiempo real. El usuario introduce un token de seguimiento (recibido por email) y el sistema consulta la API para mostrar el estado, la dirección de entrega y la fecha estimada.

---

## Índice

1. [Estructura de archivos](#estructura-de-archivos)
2. [Flujo completo de la página](#flujo-completo-de-la-página)
3. [config.php](#configphp)
4. [index.php](#indexphp)
5. [lib/api.php](#libapiphp)
6. [Cabeceras HTTP](#cabeceras-http-enviadas-a-la-api)
7. [Endpoint de la API](#endpoint-de-la-api)
8. [Ejemplos de respuesta](#ejemplos-de-respuesta-de-la-api)
9. [Vistas (escenas)](#vistas-escenas-de-la-interfaz)
10. [Seguridad](#seguridad)

---

## Estructura de archivos

```
tracking/
├── config.php                # Configuración: URL base, endpoint, email de soporte, timeout
├── index.php                 # Controlador principal + vistas HTML
├── examples-api-response.php # Documentación de ejemplos de respuesta de la API (referencia)
├── README.md                 # Este documento
├── lib/
│   └── api.php               # Cliente HTTP con funciones GET y PATCH via cURL
└── assets/
    ├── css/
    │   └── styles.css
    └── js/
        └── app.js
```

---

## Flujo completo de la página

```
Usuario accede a la URL
        │
        ├─► Con ?token=XXX en la URL (GET)
        │         │
        │         ▼
        │   track_package() → GET /api/tracking/{token}
        │         │
        │         ├─► 200 OK ──────────────────► Scene: tracking (muestra datos)
        │         └─► 404 / error ─────────────► Página 404 completa y exit
        │
        └─► Sin token (acceso directo)
                  │
                  ▼
            Scene: search (formulario de búsqueda)
                  │
                  ▼ [usuario envía formulario POST]
            Validación: token no vacío
                  │
                  ▼
            track_package() → GET /api/tracking/{token}
                  │
                  ├─► 200 OK ──────────────────► Scene: tracking (muestra datos)
                  └─► error ───────────────────► Mensaje de error en formulario
```

---

## config.php

Archivo de configuración simple que devuelve un array PHP. Se carga con `require` en `index.php`.

```php
<?php
return [
    'base_url'                => 'https://api.tolosaerronka.es',
    'tracking_endpoint'       => '/api/tracking',
    'login_url'               => '/login',
    'support_email'           => 'support@tolosaerronka.es',
    'request_timeout_seconds' => 10,
];
```

| Clave | Valor | Descripción |
|---|---|---|
| `base_url` | `https://api.tolosaerronka.es` | Dominio de la API backend |
| `tracking_endpoint` | `/api/tracking` | Ruta base del endpoint de seguimiento (referencia; la URL real se construye en `index.php`) |
| `login_url` | `/login` | URL de login (disponible para uso futuro) |
| `support_email` | `support@tolosaerronka.es` | Email de soporte |
| `request_timeout_seconds` | `10` | Timeout en segundos para las llamadas HTTP |

---

## index.php

Controlador principal que combina lógica de negocio PHP con el renderizado HTML de las vistas.

### Carga de dependencias e inicialización

```php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/api.php';

$scene         = 'search';   // Vista por defecto: formulario de búsqueda
$packageData   = null;
$errors        = [];
$trackingToken = trim((string) ($_GET['token'] ?? ''));
```

`__DIR__` asegura que las rutas de `require` funcionen sin importar desde dónde se invoque el archivo.

### Función auxiliar de escape HTML

```php
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
```

Toda variable de usuario o dato de la API que se imprima en HTML pasa por esta función para prevenir XSS. Acepta `null` sin errores gracias al cast a `string`.

---

### Función `track_package()`

Es la función central del módulo. Construye la URL con el token, llama a la API mediante GET y normaliza la respuesta.

```php
function track_package(string $baseUrl, string $trackingToken, int $timeout): array
{
    $url = rtrim($baseUrl, '/') . '/api/tracking/' . urlencode($trackingToken);
    $response = api_get_json($url, $timeout);

    if (!empty($response['error'])) {
        return ['success' => false, 'message' => 'Konexio-errorea: ' . $response['error']];
    }

    $status = (int) ($response['status'] ?? 0);
    $data   = $response['data'] ?? [];

    if ($status === 200) {
        return ['success' => true, 'data' => $data];
    }

    if ($status === 404) {
        return ['success' => false, 'message' => 'Jarraipen-tokena baliogabea edo iraungita dago.'];
    }

    return ['success' => false, 'message' => $data['message'] ?? 'Errorea paketea bilatzean (Errorea ' . $status . ').'];
}
```

**Construcción de la URL:**

```php
$url = rtrim($baseUrl, '/') . '/api/tracking/' . urlencode($trackingToken);
// Ejemplo: https://api.tolosaerronka.es/api/tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
```

- `rtrim($baseUrl, '/')` elimina la barra final del `base_url` si existiera, evitando dobles barras al concatenar.
- `urlencode($trackingToken)` asegura que caracteres especiales en el token no rompan la URL.

**Tabla de retornos:**

| Situación | `success` | Contenido | Resultado en pantalla |
|---|---|---|---|
| API responde `200` | `true` | `data` con datos del paquete | Vista de seguimiento con datos |
| API responde `404` | `false` | `message` fijo en euskera | Mensaje de error / página 404 |
| Error de red / timeout | `false` | `message` con el error de cURL | Mensaje de error |
| Otro código HTTP | `false` | `message` de la API o genérico | Mensaje de error |

---

### Modo GET: token en la URL

Cuando el usuario llega directamente con el token en la URL (`?token=XXX`), el módulo lo procesa automáticamente sin esperar a que envíe un formulario:

```php
$trackingToken = trim((string) ($_GET['token'] ?? ''));

if ($trackingToken !== '' && $scene === 'search') {
    $result = track_package($config['base_url'], $trackingToken, $config['request_timeout_seconds']);
    if ($result['success']) {
        $scene       = 'tracking';
        $packageData = $result['data'];
    } else {
        http_response_code(404);
        ?><!doctype html>
        <html lang="es">
        <!-- ... Página 404 completa con CSS inline ... -->
        </html>
        <?php
        exit;
    }
}
```

Si el token de la URL no es válido, se devuelve una **página 404 completa** con `exit`, cortando cualquier renderizado posterior. Esto da una experiencia de error real al usuario (incluyendo el código HTTP correcto para SEO/crawlers).

---

### Modo POST: formulario enviado por el usuario

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingToken = trim((string) ($_POST['tracking_token'] ?? ''));

    if (empty($trackingToken)) {
        $errors[] = 'Mesedez, sartu zure jarraipen-tokena.';
    } else {
        $result = track_package($config['base_url'], $trackingToken, $config['request_timeout_seconds']);
        if ($result['success']) {
            $scene       = 'tracking';
            $packageData = $result['data'];
        } else {
            $errors[] = $result['message'];
        }
    }
}
```

A diferencia del modo GET, un token inválido enviado por formulario **no produce un 404 completo**: el error se añade al array `$errors[]` y se muestra en el formulario para que el usuario pueda corregirlo. La razón: por POST el usuario está intentando buscar activamente, por GET se asume que el enlace fue compartido.

---

### Mapa de estados del paquete

```php
$statusMap = [
    'pending'          => ['label' => 'Zain',              'icon' => '📋', 'class' => 'pending'],
    'processing'       => ['label' => 'Prozesatzen',       'icon' => '⚙️', 'class' => 'pending'],
    'in_warehouse'     => ['label' => 'Biltegian',         'icon' => '🏢', 'class' => 'pending'],
    'in_transit'       => ['label' => 'Garraioan',         'icon' => '🚚', 'class' => 'pending'],
    'out_for_delivery' => ['label' => 'Banaketara irten',  'icon' => '📦', 'class' => 'pending'],
    'in_delivery'      => ['label' => 'Banatzen',          'icon' => '📦', 'class' => 'pending'],
    'delivered'        => ['label' => 'Entregatua',        'icon' => '✓',  'class' => 'delivered'],
    'failed'           => ['label' => 'Entrega huts egin', 'icon' => '✗',  'class' => 'failed'],
    'returned'         => ['label' => 'Itzulita',          'icon' => '↩️', 'class' => 'failed'],
];
```

El campo `status` que devuelve la API es un slug en inglés. Este mapa lo traduce a:
- **Etiqueta en euskera** para mostrar al usuario.
- **Icono emoji** para representación visual rápida.
- **Clase CSS** para colores diferenciados (`pending` / `delivered` / `failed`).

Si la API devuelve un status desconocido, se usa un fallback genérico:

```php
$statusInfo = $statusMap[$status] ?? ['label' => 'Estado desconocido', 'icon' => '?', 'class' => 'pending'];
```

---

### Renderizado de datos del paquete

Cada campo de la respuesta de la API se muestra **condicionalmente**, solo si existe:

```php
<?php if (!empty($packageData['recipient_name'])): ?>
  <div class="pkg-info">
    <div class="pkg-info-label">Hartzailea</div>
    <div class="pkg-info-value"><?php echo h($packageData['recipient_name']); ?></div>
  </div>
<?php endif; ?>

<?php if (!empty($packageData['address'])): ?>
  <div class="pkg-info">
    <div class="pkg-info-label">Entrega helbidea</div>
    <div class="pkg-info-value">
      <?php echo h($packageData['address']['street'] ?? ''); ?>
      <?php if (!empty($packageData['address']['city'])): ?>
        <br><?php echo h($packageData['address']['city']); ?>
      <?php endif; ?>
      <?php if (!empty($packageData['address']['postal_code'])): ?>
        , <?php echo h($packageData['address']['postal_code']); ?>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
```

**Campos que se muestran (todos opcionales):**

| Campo API | Etiqueta mostrada (euskera) | Significado |
|---|---|---|
| `tracking_code` | Título de la página | Código de seguimiento |
| `status` | (vía `$statusMap`) | Estado del envío |
| `recipient_name` | Hartzailea | Destinatario |
| `address.street` | Entrega helbidea | Calle |
| `address.city` | (continuación) | Ciudad |
| `address.postal_code` | (continuación) | Código postal |
| `estimated_delivery` | Entrega estimatua | Fecha estimada |
| `last_update` | Azken eguneratzea | Última actualización |

El uso de `?? ''` y `!empty()` evita errores si la API devuelve respuestas parciales.

---

## lib/api.php

Cliente HTTP que usa **cURL exclusivamente** (sin fallback a streams, a diferencia de `resetPassword`). Expone dos funciones: `api_get_json()` para GET y `api_patch_json()` para PATCH.

### Función `api_get_json()`

Realiza peticiones HTTP GET para obtener datos de la API. Es la que usa el flujo principal de tracking.

```php
function api_get_json(string $url, int $timeoutSeconds = 10): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $rawBody   = curl_exec($ch);
        $curlError = curl_error($ch);
        $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false) {
            return [
                'status' => 0,
                'data'   => null,
                'error'  => $curlError ?: 'Fallo desconocido en CURL',
            ];
        }

        return [
            'status' => $status,
            'data'   => json_decode($rawBody, true),
            'error'  => null,
        ];
    }

    return [
        'status' => 0,
        'data'   => null,
        'error'  => 'CURL no está instalado en este servidor.',
    ];
}
```

**Detalles importantes:**

- No envía body (es GET).
- `CURLOPT_RETURNTRANSFER => true` hace que `curl_exec()` devuelva el body como string en lugar de imprimirlo.
- `CURLOPT_TIMEOUT` es el timeout total; `CURLOPT_CONNECTTIMEOUT = 5` es el timeout solo para la fase de conexión.
- `curl_getinfo($ch, CURLINFO_HTTP_CODE)` extrae el código HTTP de la respuesta.
- Si cURL no está instalado, devuelve un error descriptivo en lugar de intentar un fallback.

### Función `api_patch_json()`

Realiza peticiones HTTP PATCH (no se usa actualmente en el flujo de tracking, pero está disponible por simetría con el módulo de resetPassword).

```php
function api_patch_json(string $url, array $payload, int $timeoutSeconds = 10): array
{
    $jsonPayload = json_encode($payload);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($jsonPayload),
            ],
            CURLOPT_TIMEOUT        => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $rawBody   = curl_exec($ch);
        $curlError = curl_error($ch);
        $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawBody === false) {
            return [
                'status' => 0,
                'data'   => null,
                'error'  => $curlError ?: 'Fallo desconocido en CURL',
            ];
        }

        return [
            'status' => $status,
            'data'   => json_decode($rawBody, true),
            'error'  => null,
        ];
    }

    return [
        'status' => 0,
        'data'   => null,
        'error'  => 'CURL no está instalado en este servidor.',
    ];
}
```

`CURLOPT_CUSTOMREQUEST => 'PATCH'` fuerza el método HTTP PATCH (cURL no tiene una opción específica para PATCH).

**Valor de retorno — array con estas claves (ambas funciones):**

| Clave | Tipo | Descripción |
|---|---|---|
| `status` | `int` | Código HTTP (`0` si no se pudo conectar) |
| `data` | `array\|null` | Body JSON decodificado (`json_decode` con `true`), o `null` si no es JSON |
| `error` | `string\|null` | Mensaje de error de cURL, o `null` si la petición fue bien |

> **Nota:** A diferencia de `resetPassword/lib/api.php`, este cliente **no** tiene la función `normalize_api_response()` ni la clave `ok` en el retorno. `index.php` comprueba directamente `$response['error']` y `$response['status']`.

---

## Cabeceras HTTP enviadas a la API

**GET (seguimiento de paquete):**
```
Content-Type: application/json
Accept: application/json
```

**PATCH:**
```
Content-Type: application/json
Accept: application/json
Content-Length: <longitud en bytes>
```

No se envían cabeceras de autenticación. El token de seguimiento va en la URL, no en las cabeceras (es un endpoint público).

---

## Endpoint de la API

### `GET /api/tracking/{trackingToken}`

URL real construida en `track_package()`:
```
https://api.tolosaerronka.es/api/tracking/{trackingToken}
```

**Características:**
- **No requiere autenticación.** El token UUID actúa como credencial pública de un solo uso.
- Endpoint **público** para que los destinatarios rastreen sin tener cuenta.
- El token es opaco (UUID v4 típicamente).

**Ejemplo de petición HTTP:**

```http
GET /api/tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6 HTTP/1.1
Host: api.tolosaerronka.es
Accept: application/json
Content-Type: application/json
```

---

## Ejemplos de respuesta de la API

Documentados en `examples-api-response.php` (archivo de referencia interna, no se sirve al usuario):

### HTTP 200 — Paquete en tránsito

```json
{
  "tracking_code": "TRK-2024-ABCD1234",
  "recipient_name": "Maria Garcia",
  "status": "in_transit",
  "estimated_delivery": "2024-04-20",
  "address": {
    "street": "Calle Mayor 10",
    "city": "Bilbao",
    "postal_code": "48001"
  },
  "last_update": "2024-04-19T09:00:00.000Z"
}
```

### HTTP 200 — Paquete entregado

```json
{
  "tracking_code": "TRK-2024-EFGH5678",
  "recipient_name": "Juan López Martínez",
  "status": "delivered",
  "estimated_delivery": "2024-04-19",
  "address": {
    "street": "Avenida Diagonal 456, Apt 5B",
    "city": "Barcelona",
    "postal_code": "08019"
  },
  "last_update": "2024-04-19T16:45:00.000Z"
}
```

### HTTP 200 — Paquete en almacén

```json
{
  "tracking_code": "TRK-2024-IJKL9012",
  "recipient_name": "Carlos Sánchez",
  "status": "in_warehouse",
  "estimated_delivery": "2024-04-22",
  "address": {
    "street": "Paseo de la Castellana 789",
    "city": "Madrid",
    "postal_code": "28046"
  },
  "last_update": "2024-04-18T10:30:00.000Z"
}
```

### HTTP 404 — Token inválido o expirado

```json
{
  "message": "Invalid or expired tracking token"
}
```

### HTTP 500 — Error del servidor

```json
{
  "message": "Internal server error"
}
```

---

## Respuestas de la API y cómo se manejan

| HTTP | Situación | Comportamiento en PHP |
|---|---|---|
| `200` | Token válido, paquete encontrado | `$scene = 'tracking'`, se muestran los datos |
| `404` | Token inválido o expirado | Página 404 completa (modo GET) o mensaje de error en formulario (modo POST) |
| `500` | Error interno de la API | Mensaje genérico con el código de error |
| `0` + error cURL | Timeout, DNS, sin red | Mensaje `'Konexio-errorea: ...'` con el detalle de cURL |

---

## Vistas (escenas) de la interfaz

### 1. Formulario de búsqueda (`$scene === 'search'`)

Se muestra cuando el usuario accede sin token o cuando el formulario POST falla:

```php
<?php if ($scene === 'search'): ?>
  <h1 class="form-title">Zure bidalketa kokatu</h1>

  <?php foreach ($errors as $error): ?>
    <div class="alert">
      <?php echo h($error); ?>
    </div>
  <?php endforeach; ?>

  <form method="post" novalidate>
    <input name="tracking_token" type="text"
           placeholder="Itsatsi tokena baieztatze-emailetik"
           autofocus autocomplete="off"
           value="<?php echo h($trackingToken); ?>"/>
    <button type="submit">Paketea jarraitu</button>
  </form>
<?php endif; ?>
```

- `autofocus` pone el cursor directamente en el campo al cargar.
- `autocomplete="off"` evita que el navegador autorrellene con tokens antiguos.
- `novalidate` desactiva la validación HTML5 a propósito (la valida PHP).

### 2. Vista de seguimiento (`$scene === 'tracking'`)

Se muestra cuando la API devuelve datos válidos:

```php
<?php elseif ($scene === 'tracking' && $packageData): ?>
  <?php
    $status     = $packageData['status'] ?? 'unknown';
    $statusInfo = $statusMap[$status] ?? ['label' => 'Estado desconocido', 'icon' => '?', 'class' => 'pending'];
  ?>

  <h1><?php echo h($packageData['tracking_code'] ?? 'Zure paketea'); ?></h1>

  <div class="tracking-header">
    <div class="status-label"><?php echo h($statusInfo['label']); ?></div>
    <div class="status-icon"><?php echo $statusInfo['icon']; ?></div>
  </div>

  <!-- ... campos adicionales del paquete ... -->

  <form method="post">
    <button type="submit" class="btn-secondary">Beste pakete bat bilatu</button>
  </form>
<?php endif; ?>
```

El botón "Buscar otro paquete" envía un POST vacío, lo que hace que `$trackingToken` quede vacío y vuelva a la vista del formulario.

### 3. Página 404 completa (token inválido en GET)

Cuando se accede con un token inválido directamente en la URL (`?token=XXX`), se devuelve una página 404 autocontenida (con su propio CSS inline para que funcione siempre):

```php
http_response_code(404);
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <title>404 — pakAG</title>
  <style>
    /* CSS inline completo: tema oscuro, gradiente, número 404 grande, botón */
  </style>
</head>
<body>
  <div class="page">
    <div class="card">
      <div class="num-404">404</div>
      <h2>Esta página no existe</h2>
      <p>Parece que te has perdido en la ruta.</p>
      <a class="back-btn" href="/">Volver al inicio</a>
    </div>
  </div>
</body>
</html>
<?php
exit; // Detiene cualquier renderizado posterior
```

El `exit` es crucial: garantiza que no se imprima el HTML del flujo principal después de la página 404.

---

## Panel derecho decorativo

El panel derecho de la interfaz no tiene funcionalidad dinámica relevante. Es visual/informativo y contiene:
- Una **cuadrícula SVG** generada con PHP para el fondo.
- Una **ruta de mapa simulada** con SVG estático (`polyline` + `circle`).
- **Tres tarjetas flotantes** de ejemplo (paquete en tránsito, progreso de ruta, mapa simulado).
- Un titular en euskera.

La cuadrícula SVG se genera con PHP usando un bucle `for`:

```php
<svg class="map-grid" style="position:absolute;inset:0;width:100%;height:100%" xmlns="http://www.w3.org/2000/svg">
  <?php for($i=0; $i<20; $i++): ?>
    <line x1="0" y1="<?php echo $i*60; ?>" x2="100%" y2="<?php echo $i*60; ?>"
          stroke="var(--border-normal)" stroke-width="1"/>
  <?php endfor; ?>
  <?php for($i=0; $i<20; $i++): ?>
    <line x1="<?php echo $i*80; ?>" y1="0" x2="<?php echo $i*80; ?>" y2="100%"
          stroke="var(--border-normal)" stroke-width="1"/>
  <?php endfor; ?>
</svg>
```

Y otra ruta con polyline:

```php
<svg class="map-route" style="position:absolute;inset:0;width:100%;height:100%;opacity:.4">
  <polyline points="120,280 240,160 360,240 480,120"
            stroke="var(--accent-primary)" stroke-width="2"
            fill="none" stroke-dasharray="6 4"/>
  <circle cx="120" cy="280" r="6" fill="var(--st-delivered-fg)"/>
  <circle cx="240" cy="160" r="6" fill="var(--st-delivered-fg)"/>
  <circle cx="360" cy="240" r="8" fill="var(--accent-light)"/>
  <circle cx="480" cy="120" r="6" fill="var(--st-assigned-fg)"/>
</svg>
```

---

## examples-api-response.php

Archivo de **documentación interna**. **No se sirve al usuario final** (no tiene rutas de acceso público definidas, no se incluye en `index.php`).

Contiene arrays PHP que muestran la estructura exacta de cada tipo de respuesta de la API, así como la descripción del flujo de integración:

```
1. Usuario recibe email con URL de seguimiento:
   https://pakag.local/tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6

2. La app detecta el token desde ?token=...

3. PHP hace GET a:
   https://api.tolosaerronka.es/api/tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6

4. La API devuelve los datos del paquete (JSON)

5. La app muestra la información al usuario
```

Sirve como referencia rápida para el equipo de desarrollo: "¿Qué campos espero recibir?" → mira este archivo.

---

## Seguridad

| Medida | Implementación |
|---|---|
| **Escape XSS** | Todo dato de API o usuario se imprime a través de `h()` |
| **`urlencode` en URL** | El token se codifica antes de insertarse en la URL de la API |
| **HTTP 404 real** | Tokens inválidos en GET devuelven un código 404 real, no solo un mensaje |
| **`novalidate` en formulario** | La validación HTML5 del navegador se desactiva; valida PHP |
| **`autocomplete="off"`** | El campo de token no se autocompleta con valores anteriores |
| **Timeout fijo** | 10 segundos; evita bloqueos por API lenta |
| **Sin base de datos directa** | Toda la lógica de datos pasa por la API REST; PHP no accede a ninguna BD |
| **Llamadas desde servidor** | El navegador nunca habla directamente con la API → no hay problemas de CORS |
| **Sin cabeceras sensibles** | El endpoint es público; no se envían tokens de auth ni cookies |

---

## Diferencias respecto a `resetPassword/lib/api.php`

Aunque ambos módulos comparten estructura similar, hay diferencias importantes:

| Aspecto | `tracking/lib/api.php` | `resetPassword/lib/api.php` |
|---|---|---|
| Funciones expuestas | `api_get_json()` + `api_patch_json()` | Solo `api_patch_json()` |
| Fallback sin cURL | ❌ No tiene (devuelve error) | ✅ Usa `file_get_contents` + stream context |
| Función `normalize_api_response()` | ❌ No existe | ✅ Sí existe |
| Clave `ok` en el retorno | ❌ No la incluye | ✅ La incluye |
| Clave `raw` en el retorno | ❌ No la incluye | ✅ La incluye |
| Flags JSON al codificar | Por defecto | `JSON_UNESCAPED_SLASHES \| JSON_UNESCAPED_UNICODE` |

---

## Requisitos del entorno

- **PHP 8+** (tipado de parámetros y retornos, null coalescing).
- **Apache con `mod_rewrite`** para el routing `/{TOKEN}` → `index.php?token={TOKEN}`.
- **Extensión `curl` activa** (obligatoria, no hay fallback).
- **API backend funcionando** en la URL configurada en `config.php`.
