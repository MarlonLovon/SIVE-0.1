<?php
/**
 * SIVE 2.0 - Funciones Auxiliares Globales (Helpers)
 *
 * Colección de funciones utilitarias para sanitización XSS, formateo de moneda peruana,
 * fechas, generación de URLs, protección CSRF y detección de navegación activa.
 */

/**
 * Escapa cadenas para prevenir ataques de Cross-Site Scripting (XSS)
 *
 * @param mixed $str
 * @return string
 */
function e(mixed $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea un valor numérico como moneda nacional peruana (S/ 0.00)
 *
 * @param float|int|string|null $amount
 * @return string
 */
function formatMoney(float|int|string|null $amount): string {
    $num = is_numeric($amount) ? (float)$amount : 0.0;
    return 'S/ ' . number_format($num, 2, '.', ',');
}

/**
 * Formatea una fecha para visualización en pantalla (ej. 09/11/2026)
 *
 * @param string|null $date
 * @param string $format
 * @return string
 */
function formatDate(?string $date, string $format = 'd/m/Y'): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Formatea una fecha y hora para visualización (ej. 09/11/2026 15:45)
 *
 * @param string|null $datetime
 * @param string $format
 * @return string
 */
function formatDateTime(?string $datetime, string $format = 'd/m/Y H:i'): string {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Genera una URL absoluta o relativa basada en la constante BASE_URL
 *
 * @param string $path Ruta relativa
 * @return string
 */
function url(string $path = ''): string {
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    $cleanPath = ltrim($path, '/');

    if ($cleanPath === '') {
        return $base !== '' ? $base . '/' : '/';
    }

    return ($base !== '' ? $base . '/' : '/') . $cleanPath;
}

/**
 * Genera la URL para recursos estáticos (CSS, JS, imágenes, fuentes)
 *
 * @param string $path Ruta del asset
 * @return string
 */
function asset(string $path): string {
    return url(ltrim($path, '/'));
}

/**
 * Comprueba si la ruta actual coincide con la especificada para resaltar el menú activo
 *
 * @param string $path Ruta o prefijo a comprobar
 * @param string $activeClass Clase CSS a retornar cuando coincide (por defecto 'active')
 * @return string
 */
function isActive(string $path, string $activeClass = 'active'): string {
    $current = trim($_GET['url'] ?? '', '/');
    $target = trim($path, '/');

    // Casos para la ruta raíz o Dashboard
    if ($target === '' || $target === 'dashboard') {
        if ($current === '' || $current === 'dashboard') {
            return $activeClass;
        }
        return '';
    }

    // Coincidencia exacta o como prefijo de subruta (ej. 'ventas' coincide con 'ventas/crear')
    if ($current === $target || str_starts_with($current, $target . '/')) {
        return $activeClass;
    }

    return '';
}

/**
 * Genera u obtiene el token CSRF activo en la sesión
 *
 * @return string
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Genera un campo HTML oculto con el token CSRF
 *
 * @return string
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifica si el token CSRF recibido coincide con el de la sesión
 *
 * @param string|null $token Token a verificar (si es null se busca en $_POST o headers)
 * @return bool
 */
function verify_csrf(?string $token = null): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) {
        return false;
    }

    $submitted = $token;
    if ($submitted === null) {
        $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    }

    if (!is_string($submitted) || empty($submitted)) {
        return false;
    }

    return hash_equals($sessionToken, $submitted);
}
