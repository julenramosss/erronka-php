# resetPassword — Documentación técnica

Módulo PHP que gestiona el restablecimiento de contraseña de usuarios de **pakAG**. El usuario recibe un enlace por email con un token único; al abrirlo, este módulo valida el token contra la API y, si es válido, permite establecer una nueva contraseña.

---

## Índice

1. [Estructura de archivos](#estructura-de-archivos)
2. [Flujo completo de la página](#flujo-completo-de-la-página)
3. [config.php](#configphp)
4. [index.php](#indexphp)
5. [lib/api.php](#libapiphp)
6. [Cabeceras HTTP](#cabeceras-http-que-se-envían-a-la-api)
7. [Respuestas de la API](#respuestas-de-la-api-y-cómo-se-manejan)
8. [Vistas (escenas)](#vistas-escenas-de-la-interfaz)
9. [Seguridad](#seguridad)

---

## Estructura de archivos

```
resetPassword/
├── config.php        # Configuración: URL base, rutas, email de soporte, timeout
├── index.php         # Controlador principal + vistas HTML
├── README.md         # Este documento
├── lib/
│   └── api.php       # Cliente HTTP (PATCH) con doble backend: cURL / streams
└── assets/
    ├── css/
    │   └── styles.css
    └── js/
        └── app.js
```

---

## Flujo completo de la página

```
Usuario abre URL con ?token=...
        │
        ▼
index.php lee el token de $_GET
        │
        ├─► Token vacío ──────────────────────► Scene: expired (HTTP 404)
        │
        ▼
validate_reset_token() → PATCH /api/auth/changePwd
        │
        ├─► valid = false (401) ──────────────► Scene: expired (HTTP 404)
        ├─► valid = null (error de red) ──────► Scene: error   (HTTP 503)
        │
        ▼
Scene: form (HTTP 200)
        │
        ▼ [usuario envía POST]
Validación local del formulario
        │
        ├─► errores de validación ────────────► Vuelve al formulario con mensajes
        │
        ▼
change_password() → PATCH /api/auth/changePwd
        │
        ├─► success = true  (200) ────────────► Scene: success
        ├─► expired = true  (401) ────────────► Scene: expired (HTTP 404)
        └─► error genérico        ────────────► Vuelve al formulario con mensaje
```

---

## config.php

Archivo de configuración simple que devuelve un array PHP. Se carga con `require` en `index.php`.

```php
<?php
return [
    'base_url'                => 'https://api.tolosaerronka.es',
    'change_pwd_path'         => '/api/auth/changePwd',
    'login_url'               => '/login',
    'support_email'           => 'support@tolosaerronka.es',
    'request_timeout_seconds' => 10,
];
```

| Clave | Valor | Descripción |
|---|---|---|
| `base_url` | `https://api.tolosaerronka.es` | Dominio de la API backend |
| `change_pwd_path` | `/api/auth/changePwd` | Ruta del endpoint de cambio de contraseña |
| `login_url` | `/login` | URL a la que redirige el botón "Iniciar sesión" en la pantalla de éxito |
| `support_email` | `support@tolosaerronka.es` | Email de soporte mostrado en la pantalla de token expirado |
| `request_timeout_seconds` | `10` | Tiempo máximo de espera para llamadas HTTP |

La URL final que se construye en `index.php` es:

```php
$endpointUrl = rtrim($config['base_url'], '/') . $config['change_pwd_path'];
// Resultado: https://api.tolosaerronka.es/api/auth/changePwd
```

`rtrim($config['base_url'], '/')` elimina la barra final del `base_url` si existiera, evitando dobles barras al concatenar la ruta.

---

## index.php

Archivo principal. Hace tres cosas: **lógica de negocio** (PHP al inicio), **renderizado HTML** (mezclado con PHP), y **gestión del estado de pantalla** a través de la variable `$scene`.

### Carga de dependencias e inicialización

```php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/api.php';

$endpointUrl = rtrim($config['base_url'], '/') . $config['change_pwd_path'];
$timeout = (int) ($config['request_timeout_seconds'] ?? 10);
```

`__DIR__` garantiza que las rutas funcionen sin importar desde dónde se invoque el archivo. El operador `??` (null coalescing) protege contra una clave de configuración ausente.

### Función auxiliar de escape HTML

Toda la salida de datos al HTML pasa por esta función para prevenir XSS:

```php
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
```

- `ENT_QUOTES` escapa tanto comillas simples como dobles.
- Acepta `null` sin errores gracias al cast a `string`.
- Se invoca en todos los lugares donde se imprime una variable: `<?php echo h($variable); ?>`.

### Lectura del token desde la URL

```php
$token = trim((string) ($_GET['token'] ?? $_GET['reset_pwd_token'] ?? ''));
$tokenPreview = $token !== ''
    ? (strlen($token) > 20 ? substr($token, 0, 20) . '…' : $token)
    : 'token gabe';
```

Admite dos nombres de parámetro GET: `?token=` y `?reset_pwd_token=`, por compatibilidad con distintos formatos de email. La preview trunca el token a 20 caracteres para mostrarlo en pantalla sin exponer el valor completo.

---

### Función `validate_reset_token()`

Verifica con la API si el token es válido **antes** de mostrar el formulario. Llama al mismo endpoint PATCH que el cambio de contraseña, pero solo envía el token (sin contraseña nueva).

```php
function validate_reset_token(string $endpointUrl, string $token, int $timeout): array
{
    $response = api_patch_json($endpointUrl, ['reset_pwd_token' => $token], $timeout);

    if ($response['error']) {
        return [
            'valid'           => null,
            'transport_error' => $response['error'],
            'status'          => $response['status'],
            'message'         => 'Ezin izan da esteka orain berrikusi.',
        ];
    }

    $status = (int) ($response['status'] ?? 0);
    $data   = $response['data'] ?? [];

    if ($status === 200 && array_key_exists('valid', $data)) {
        return ['valid' => (bool) $data['valid'], 'transport_error' => null, 'status' => $status, 'message' => null];
    }

    if ($status === 401) {
        return ['valid' => false, 'transport_error' => null, 'status' => $status, 'message' => $data['message'] ?? 'Token baliogabea edo iraungita'];
    }

    return ['valid' => null, 'transport_error' => null, 'status' => $status, 'message' => $data['message'] ?? 'Ezin izan da esteka orain berrikusi.'];
}
```

**Tabla de retornos:**

| Situación | `valid` | `status` | Resultado en pantalla |
|---|---|---|---|
| API responde 200 con `{"valid": true}` | `true` | `200` | Se muestra el formulario |
| API responde 200 con `{"valid": false}` | `false` | `200` | Pantalla 404 expirado |
| API responde 401 (token inválido/expirado) | `false` | `401` | Pantalla 404 expirado |
| Error de red / timeout | `null` | `0` | Pantalla error 503 |
| Otro código HTTP inesperado | `null` | `XXX` | Pantalla error 503 |

**Tres estados posibles para `valid`:**

- `true` → token válido, mostrar formulario
- `false` → token inválido confirmado por la API, mostrar 404
- `null` → no se pudo determinar (error de red/servidor), mostrar 503

---

### Función `change_password()`

Se ejecuta cuando el usuario envía el formulario (método POST) y las validaciones locales pasan. Envía el token y la nueva contraseña a la API.

```php
function change_password(string $endpointUrl, string $token, string $password, int $timeout): array
{
    $response = api_patch_json($endpointUrl, [
        'reset_pwd_token' => $token,
        'new_password'    => $password,
    ], $timeout);

    if ($response['error']) {
        return [
            'success' => false,
            'expired' => false,
            'message' => 'Ezin izan da APIrekin konektatu. Egiaztatu oinarri-URLa edo zerbitzaria.',
            'status'  => 0,
        ];
    }

    $status = (int) ($response['status'] ?? 0);
    $data   = $response['data'] ?? [];

    if ($status === 200) {
        return ['success' => true,  'expired' => false, 'message' => $data['message'] ?? 'Pasahitza behar bezala aldatu da', 'status' => $status];
    }

    if ($status === 401) {
        return ['success' => false, 'expired' => true,  'message' => $data['message'] ?? 'Token baliogabea edo iraungita', 'status' => $status];
    }

    return ['success' => false, 'expired' => false, 'message' => $data['message'] ?? 'Ezin izan da pasahitza eguneratu.', 'status' => $status];
}
```

**Payload que se envía a la API:**

```json
{
  "reset_pwd_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "new_password": "MiNuevaContraseña123!"
}
```

**Tabla de retornos:**

| HTTP | `success` | `expired` | Resultado |
|---|---|---|---|
| `200` | `true` | `false` | Pantalla de éxito |
| `401` | `false` | `true` | Pantalla 404 expirado |
| Error de red | `false` | `false` | Mensaje de error en formulario |
| Otro código | `false` | `false` | Mensaje de error en formulario |

---

### Validación local del formulario (antes de llamar a la API)

PHP valida el formulario del lado del servidor antes de hacer ninguna llamada HTTP, evitando peticiones innecesarias:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $scene === 'form') {
    $formPassword = (string) ($_POST['new_password'] ?? '');
    $formConfirm  = (string) ($_POST['confirm_password'] ?? '');

    if ($formPassword === '')              $errors[] = 'Pasahitz berria nahitaezkoa da.';
    if (strlen($formPassword) < 6)        $errors[] = 'Pasahitzak gutxienez 6 karaktere izan behar ditu.';
    if ($formPassword !== $formConfirm)   $errors[] = 'Pasahitzak ez datoz bat.';

    if (empty($errors)) {
        $changeResult = change_password($endpointUrl, $token, $formPassword, $timeout);
        if ($changeResult['success']) {
            $scene = 'success';
            $successMessage = $changeResult['message'];
        } elseif ($changeResult['expired']) {
            $scene = 'expired';
            $pageHttpCode = 404;
        } else {
            $errors[] = $changeResult['message'];
        }
    }
}
```

**Reglas de validación:**
1. La contraseña no puede estar vacía.
2. Mínimo 6 caracteres.
3. La confirmación debe coincidir exactamente.

Solo si las tres reglas pasan se realiza la llamada a la API.

---

### Gestión de escenas (vistas)

La variable `$scene` controla qué HTML se renderiza:

```php
$scene = 'form'; // valor por defecto

// Token vacío → expired
if ($token === '') {
    $scene = 'expired';
    $pageHttpCode = 404;
} else {
    $tokenCheck = validate_reset_token($endpointUrl, $token, $timeout);
    if ($tokenCheck['valid'] === false) {
        $scene = 'expired';
        $pageHttpCode = 404;
    } elseif ($tokenCheck['valid'] === null) {
        $scene = 'error';
        $errors[] = $tokenCheck['message'];
        $pageHttpCode = 503;
    }
}
```

| `$scene` | `$pageHttpCode` | Descripción |
|---|---|---|
| `'form'` | `200` | Formulario de cambio de contraseña |
| `'success'` | `200` | Cambio realizado correctamente |
| `'expired'` | `404` | Token inválido, expirado o ya usado |
| `'error'` | `503` | Error de conectividad con la API |

La escena `'expired'` se renderiza con una página 404 completa **antes** que el resto del HTML y termina con `exit`:

```php
if ($scene === 'expired') {
    http_response_code(404);
    ?><!doctype html>
    <html lang="es">
    <!-- ... HTML completo de página 404 (autocontenido, con CSS inline) ... -->
    </html>
    <?php
    exit;
}

http_response_code($pageHttpCode); // Para las demás escenas
```

El `exit` es crucial: detiene la ejecución y evita que se renderice el HTML del flujo principal después.

### Selección de título dinámico (match)

Usando la sintaxis `match` de PHP 8:

```php
<title><?php echo match ($scene) {
    'success' => 'Pasahitza eguneratuta — pakAG',
    'expired' => '404 — Esteka baliogabea — pakAG',
    'error'   => 'Errorea — pakAG',
    default   => 'Pasahitza aldatu — pakAG',
}; ?></title>
```

---

## lib/api.php

Cliente HTTP que expone una única función pública. Tiene **doble implementación**: usa **cURL** si está disponible, y cae en **streams de PHP** (`file_get_contents`) como fallback. Esto garantiza que el módulo funcione incluso en hostings donde cURL no está habilitado.

### Función `api_patch_json()`

```php
function api_patch_json(string $url, array $payload, int $timeoutSeconds = 10): array
```

**Parámetros:**

| Parámetro | Tipo | Descripción |
|---|---|---|
| `$url` | `string` | URL completa del endpoint |
| `$payload` | `array` | Datos a enviar, se serializan a JSON automáticamente |
| `$timeoutSeconds` | `int` | Timeout total en segundos (por defecto: 10) |

**Valor de retorno — array con estas claves:**

| Clave | Tipo | Descripción |
|---|---|---|
| `ok` | `bool` | `true` si HTTP 2xx |
| `status` | `int` | Código HTTP (0 si no se pudo conectar) |
| `data` | `array\|null` | Cuerpo JSON decodificado, o `null` si no es JSON válido |
| `raw` | `string` | Cuerpo de la respuesta sin procesar |
| `error` | `string\|null` | Mensaje de error de transporte, `null` si todo fue bien |

---

#### Serialización JSON

```php
$jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($jsonPayload === false) {
    return [
        'ok'     => false,
        'status' => 0,
        'data'   => null,
        'error'  => 'No se pudo serializar el cuerpo JSON.',
    ];
}
```

- `JSON_UNESCAPED_SLASHES`: no escapa las barras `/` (mejora legibilidad de URLs en el JSON).
- `JSON_UNESCAPED_UNICODE`: caracteres acentuados se mantienen como UTF-8 en lugar de `\uXXXX`.

#### Implementación con cURL (método principal)

```php
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
        CURLOPT_CONNECTTIMEOUT => min(5, $timeoutSeconds),
    ]);

    $rawBody   = curl_exec($ch);
    $curlError = curl_error($ch);
    $status    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($rawBody === false) {
        return [
            'ok'     => false,
            'status' => 0,
            'data'   => null,
            'error'  => $curlError ?: 'No se pudo conectar con la API.',
        ];
    }

    return normalize_api_response($status, $rawBody);
}
```

**Notas importantes:**

- `CURLOPT_CUSTOMREQUEST => 'PATCH'` fuerza el método HTTP PATCH (cURL no tiene una opción específica para PATCH como sí tiene para POST con `CURLOPT_POST`).
- `CURLOPT_RETURNTRANSFER => true` hace que `curl_exec()` devuelva el body como string en lugar de imprimirlo.
- `CURLOPT_CONNECTTIMEOUT` se limita a `min(5, $timeoutSeconds)` para que el timeout de conexión nunca supere al timeout total.
- Si `curl_exec()` devuelve `false` (error de red, DNS, timeout de conexión…), se retorna el error de cURL.

#### Implementación con streams (fallback)

Si cURL no está disponible en el servidor, se usa `file_get_contents` con un contexto HTTP:

```php
$context = stream_context_create([
    'http' => [
        'method'        => 'PATCH',
        'header'        => implode("\r\n", [
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($jsonPayload),
        ]),
        'content'       => $jsonPayload,
        'timeout'       => $timeoutSeconds,
        'ignore_errors' => true,   // Permite leer el body aunque HTTP sea 4xx/5xx
    ],
]);

$rawBody = @file_get_contents($url, false, $context);
```

`ignore_errors => true` es muy importante: sin él, `file_get_contents` devolvería `false` ante un 4xx/5xx, perdiendo el body con el mensaje de error de la API.

El código HTTP se extrae de la cabecera `$http_response_header` que PHP rellena automáticamente:

```php
$status = 0;
if (!empty($http_response_header)) {
    foreach ($http_response_header as $headerLine) {
        if (preg_match('/HTTP\/\S+\s+(\d{3})/', $headerLine, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }
}
```

#### Función `normalize_api_response()`

Procesamiento común para ambas implementaciones (cURL y streams):

```php
function normalize_api_response(int $status, string $rawBody): array
{
    $decoded = json_decode($rawBody, true);

    if (!is_array($decoded)) {
        $decoded = null;  // Descarta respuestas no-JSON (HTML de error, texto plano...)
    }

    return [
        'ok'    => $status >= 200 && $status < 300,
        'status'=> $status,
        'data'  => $decoded,
        'raw'   => $rawBody,
        'error' => null,
    ];
}
```

`json_decode($rawBody, true)` con el segundo parámetro `true` devuelve un array asociativo en vez de un objeto stdClass.

---

## Cabeceras HTTP que se envían a la API

Todas las peticiones PATCH incluyen:

```
Content-Type: application/json
Accept: application/json
Content-Length: <longitud exacta en bytes del JSON>
```

No se envían cabeceras de autenticación (el token forma parte del payload JSON).

---

## Respuestas de la API y cómo se manejan

### Validación de token (solo `reset_pwd_token` en el payload)

| HTTP | Contenido del body | Resultado en PHP |
|---|---|---|
| `200` | `{"valid": true}` | Muestra el formulario |
| `200` | `{"valid": false}` | Pantalla 404 expirado |
| `401` | `{"message": "..."}` | Pantalla 404 expirado |
| `503` / error red | — | Pantalla error 503 |

**Ejemplo de petición de validación:**

```http
PATCH /api/auth/changePwd HTTP/1.1
Host: api.tolosaerronka.es
Content-Type: application/json
Accept: application/json
Content-Length: 67

{"reset_pwd_token":"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."}
```

**Ejemplo de respuesta exitosa:**

```http
HTTP/1.1 200 OK
Content-Type: application/json

{"valid":true}
```

### Cambio de contraseña (`reset_pwd_token` + `new_password`)

| HTTP | Contenido del body | Resultado en PHP |
|---|---|---|
| `200` | `{"message": "..."}` | Pantalla de éxito |
| `401` | `{"message": "..."}` | Pantalla 404 expirado |
| `400` | `{"message": "..."}` | Mensaje de error en formulario |
| Error de red | — | "Ezin izan da APIrekin konektatu..." |

**Ejemplo de petición de cambio:**

```http
PATCH /api/auth/changePwd HTTP/1.1
Host: api.tolosaerronka.es
Content-Type: application/json
Accept: application/json
Content-Length: 96

{"reset_pwd_token":"eyJhbGciOiJIUzI1NiIs...","new_password":"MiNuevaContraseña123!"}
```

---

## Vistas (escenas) de la interfaz

### 1. Formulario (`$scene === 'form'`)

Formulario de dos campos (contraseña nueva + confirmación) con:
- Medidor de fortaleza de contraseña (JavaScript del lado cliente).
- Botón de mostrar/ocultar contraseña.
- Mensajes de error en línea cuando las contraseñas no coinciden.
- Estado de carga (`data-loading-state`) que se muestra mientras el formulario procesa.
- Token preview en la parte inferior para que el usuario verifique.

```php
<form data-reset-form data-form-card method="post" novalidate>
    <input id="new_password" name="new_password" type="password"
           minlength="6" placeholder="Gutxienez 6 karaktere"
           value="<?php echo h($formPassword); ?>"
           autocomplete="new-password" required data-password/>

    <input id="confirm_password" name="confirm_password" type="password"
           minlength="6" placeholder="Errepikatu pasahitza"
           value="<?php echo h($formConfirm); ?>"
           autocomplete="new-password" required data-confirm-password/>

    <button class="primary-btn" type="submit" data-submit>
        Pasahitza aldatu
    </button>
</form>
```

El atributo `novalidate` desactiva la validación HTML5 del navegador a propósito: la validación la hace PHP del lado servidor para asegurar consistencia.

### 2. Éxito (`$scene === 'success'`)

```php
<?php if ($scene === 'success'): ?>
  <h2>Pasahitza eguneratuta!</h2>
  <p>Zure pasahitza behar bezala aldatu da.</p>
  <a class="primary-btn" href="<?php echo h($config['login_url']); ?>">
    Saioa hasi
  </a>
  <div class="status-note">
    <?php echo h($successMessage ?? 'Segurtasunagatik, gainerako saio guztiak itxi dira.'); ?>
  </div>
<?php endif; ?>
```

Muestra el mensaje que devuelve la API (`$successMessage`) o el texto por defecto. Incluye botón directo al login.

### 3. Expirado / 404 (`$scene === 'expired'`)

Página completa independiente (con su propio `<html>`) que devuelve HTTP 404. Se renderiza **antes** que el resto del documento y termina con `exit`. Muestra la preview del token recibido y el email de soporte.

Esta vista es **autocontenida**: tiene su propio CSS inline para que funcione incluso si los assets externos fallan.

### 4. Error de conectividad (`$scene === 'error'`)

Devuelve HTTP 503. Muestra los mensajes de error recogidos en `$errors[]` escapados con `h()`:

```php
<?php foreach ($errors as $error): ?>
  <div class="alert fade-in" role="alert">
    <span><?php echo h($error); ?></span>
  </div>
<?php endforeach; ?>
```

---

## Panel derecho decorativo

El panel derecho de la interfaz no tiene funcionalidad dinámica. Es visual/informativo y contiene:
- Una cuadrícula SVG generada con PHP (bucle `for`).
- Tarjetas flotantes con consejos de seguridad.
- Indicador visual de fortaleza de contraseña.

La cuadrícula SVG se genera con PHP:

```php
<svg class="map-grid" style="width:100%;height:100%" xmlns="http://www.w3.org/2000/svg">
  <?php for($i=0; $i<20; $i++): ?>
    <line x1="0" y1="<?php echo $i*60; ?>" x2="100%" y2="<?php echo $i*60; ?>"
          stroke="var(--border-normal)" stroke-width="1"/>
    <line x1="<?php echo $i*80; ?>" y1="0" x2="<?php echo $i*80; ?>" y2="100%"
          stroke="var(--border-normal)" stroke-width="1"/>
  <?php endfor; ?>
</svg>
```

---

## Seguridad

| Medida | Implementación |
|---|---|
| **Escape XSS** | Toda variable de usuario pasa por `h()` antes de ser impresa en HTML |
| **Validación doble** | Validación local (PHP) + validación de negocio en la API |
| **Token opaco** | El token completo nunca se muestra en pantalla, solo una preview de 20 caracteres |
| **HTTP correcto** | 404 para tokens inválidos, 503 para errores de servicio |
| **`novalidate` en formulario** | La validación HTML5 del navegador se deshabilita a propósito para que sea PHP quien valide |
| **Timeout fijo** | 10 segundos de timeout evitan que una API lenta bloquee el proceso indefinidamente |
| **`autocomplete="new-password"`** | Evita que el navegador autorrellene el campo con la contraseña antigua guardada |
| **Llamadas desde servidor** | El navegador nunca habla directamente con la API → no hay problemas de CORS ni token expuesto en JS |

---

## Requisitos del entorno

- **PHP 8+** (se usa `match`, `null coalescing`, tipado de parámetros y retornos).
- **Apache con `mod_rewrite`** para el routing `/{TOKEN}` → `index.php?token={TOKEN}`.
- **Recomendado: extensión `curl` activa**. Si no está, el módulo cae automáticamente al fallback con `file_get_contents`.
