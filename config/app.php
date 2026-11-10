<?php
/**
 * SIVE 2.0 - Configuración General de la Aplicación
 */

define('APP_NAME', 'SIVE 2.0');
define('APP_VERSION', '2.0.0');

// Ruta base del proyecto en el servidor web.
// En XAMPP (htdocs/SIVE-0.3/) debe ser '/SIVE-0.3/'
// Para VirtualHost dedicado o producción en raíz de dominio, cambiar a '/'
define('BASE_URL', '/SIVE-0.3/');

// Zona horaria de Perú
date_default_timezone_set('America/Lima');

// Iniciar sesión de forma segura si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
