# pakAG - Sistema de Rastreo de Paquetes

Plataforma moderna para que los usuarios rastreen el estado y ubicación de sus paquetes en tiempo real usando un **token UUID único** que reciben por email.

## Configuración Rápida

Abre `config.php` y configura:

- `base_url` → URL base de tu API (ej: `http://10.23.26.64:3001`)
- `tracking_endpoint` → endpoint para rastrear paquetes (por defecto: `/api/tracking`)
- `login_url` → URL del login para usuarios registrados
- `support_email` → correo de soporte

## Características

✅ **Rastreo por token UUID** - Acceso público sin autenticación  
✅ **Información en tiempo real** - Estado, destinatario, dirección, fecha estimada  
✅ **Interfaz responsiva** - Optimizada para móvil  
✅ **Diseño moderno** - Gradientes, animaciones suaves, tema oscuro  
✅ **Búsqueda rápida** - Por URL o formulario  

## Cómo Funciona

1. **Email de confirmación** → Usuario recibe URL con token UUID único
   ```
   https://pakag.local/tracking?token=a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
   ```

2. **Búsqueda automática** → La app detecta el token en la URL y busca el paquete

3. **Llamada a API** → PHP hace GET a `/api/tracking/:trackingToken`

4. **Mostrar información** → Datos del paquete se renderizan en la página

## Estados Soportados

| Estado | Icono | Descripción |
|--------|-------|-------------|
| pending | 📋 | Pendiente |
| processing | ⚙️ | Procesando |
| in_warehouse | 🏢 | En almacén |
| in_transit | 🚚 | En tránsito |
| in_delivery | 📦 | En reparto |
| delivered | ✓ | Entregado |
| failed | ✗ | Entrega fallida |
| returned | ↩️ | Devuelto |

## API Requerida

### GET `/api/tracking/:trackingToken`

**Descripción:** Endpoint público. Retorna información de rastreo usando el token UUID único.

**Autenticación:** Ninguna requerida

**Parámetros:**
- `trackingToken` (path param) - UUID token de rastreo

**Respuesta (HTTP 200):**
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

**Errores:**
- `HTTP 404` - Token inválido o expirado → Mensaje: "Invalid or expired tracking token"
- `HTTP 500` - Error del servidor → Mensaje: "Internal server error"

## Requisitos

- PHP 8+
- Apache con `mod_rewrite`
- Extensión `curl` activa en PHP
- API backend funcionando

## Estructura de Archivos

```
├── index.php                    # Controlador y vista principal
├── config.php                   # Configuración (editable)
├── lib/
│   └── api.php                  # Funciones GET/PATCH para API
├── assets/
│   ├── css/styles.css           # Estilos + tema oscuro
│   └── js/app.js                # Interactividad (copiar, refresh)
├── examples-api-response.php    # Ejemplos de respuestas de API
└── README.md                    # Este archivo
```

## Notas Técnicas

- Las llamadas a la API se hacen desde PHP (servidor) para evitar CORS
- No requiere autenticación (endpoint público)
- El token se valida en la URL con parámetro `?token=uuid`
- Los datos se renderizan automáticamente desde la respuesta JSON
- Estilos con variables CSS para fácil personalización
- Tiempo de timeout para API calls: 10 segundos (configurable)

## Ejemplos de Uso

### Acceso directo por URL
```
https://pakag.local/tracking?token=a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
```

### Búsqueda manual
El usuario puede copiar/pegar su token en el formulario en la página principal

### Para desarrolladores
Revisa `examples-api-response.php` para ver la estructura de respuestas esperadas


