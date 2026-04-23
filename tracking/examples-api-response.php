<?php
/**
 * EJEMPLO DE RESPUESTA DE API PARA RASTREO DE PAQUETES
 * 
 * Este archivo muestra la estructura de la respuesta del endpoint:
 * GET /api/tracking/:trackingToken
 * 
 * Endpoint público sin autenticación para que los destinatarios rastreen sus paquetes
 * usando el token UUID único de rastreo que reciben por email.
 */

// Ejemplo 1: Respuesta exitosa - Paquete en tránsito (HTTP 200)
$exampleResponse200InTransit = [
    'tracking_code' => 'TRK-2024-ABCD1234',
    'recipient_name' => 'Maria Garcia',
    'status' => 'in_transit',
    'estimated_delivery' => '2024-04-20',
    'address' => [
        'street' => 'Calle Mayor 10',
        'city' => 'Bilbao',
        'postal_code' => '48001',
    ],
    'last_update' => '2024-04-19T09:00:00.000Z',
];

// Ejemplo 2: Respuesta exitosa - Paquete entregado (HTTP 200)
$exampleResponse200Delivered = [
    'tracking_code' => 'TRK-2024-EFGH5678',
    'recipient_name' => 'Juan López Martínez',
    'status' => 'delivered',
    'estimated_delivery' => '2024-04-19',
    'address' => [
        'street' => 'Avenida Diagonal 456, Apt 5B',
        'city' => 'Barcelona',
        'postal_code' => '08019',
    ],
    'last_update' => '2024-04-19T16:45:00.000Z',
];

// Ejemplo 3: Respuesta exitosa - Paquete en almacén (HTTP 200)
$exampleResponse200InWarehouse = [
    'tracking_code' => 'TRK-2024-IJKL9012',
    'recipient_name' => 'Carlos Sánchez',
    'status' => 'in_warehouse',
    'estimated_delivery' => '2024-04-22',
    'address' => [
        'street' => 'Paseo de la Castellana 789',
        'city' => 'Madrid',
        'postal_code' => '28046',
    ],
    'last_update' => '2024-04-18T10:30:00.000Z',
];

// Ejemplo 4: Token inválido o expirado (HTTP 404)
$exampleResponse404 = [
    'message' => 'Invalid or expired tracking token',
];

// Ejemplo 5: Error del servidor (HTTP 500)
$exampleResponse500 = [
    'message' => 'Internal server error',
];

/**
 * URLS DE EJEMPLO
 * 
 * Búsqueda con token en URL:
 * GET /tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
 * 
 * El formulario enviará POST a /?token=a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
 * y la app hará GET /api/tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
 */

/**
 * INTEGRACIÓN EN LA APP
 * 
 * 1. Usuario recibe email con tracking URL:
 *    https://pakag.local/tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
 * 
 * 2. Al acceder, la app detecta el token desde URL (?token=...)
 * 
 * 3. PHP hace GET a: http://10.23.26.64:3001/api/tracking/a1b2c3d4-e5f6-47g8-h9i0-j1k2l3m4n5o6
 * 
 * 4. La API devuelve los datos del paquete
 * 
 * 5. La app muestra la información al usuario
 */
