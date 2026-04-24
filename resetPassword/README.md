# Reset password PHP

## Qué tocar rápido

Abre `config.php` y cambia esto:

- `base_url` -> URL base de tu API
- `change_pwd_path` -> endpoint del cambio de contraseña
- `login_url` -> URL del login a donde quieres redirigir
- `support_email` -> correo de soporte visible en pantalla

## Cómo funciona

- `GET /TOKEN` valida el token contra la API.
- Si el token no existe o ha caducado, responde con HTTP `404` y muestra la pantalla de enlace inválido/caducado.
- Si el token es válido, muestra el formulario.
- `POST` al mismo `index.php` envía la nueva contraseña al endpoint backend desde PHP, evitando problemas de CORS en navegador.

## Requisitos

- PHP 8+
- Apache con `mod_rewrite` habilitado
- Recomendado: extensión `curl` activa en PHP

## Estructura

- `index.php` -> controlador y vista principal
- `config.php` -> configuración editable
- `lib/api.php` -> llamadas PATCH a la API
- `assets/css/styles.css` -> estilos
- `assets/js/app.js` -> UX del formulario
- `.htaccess` -> routing para `/TOKEN`
