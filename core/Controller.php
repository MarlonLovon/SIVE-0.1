<?php
/**
 * SIVE 2.0 - Controlador Base del Sistema MVC
 *
 * Provee métodos reutilizables para renderizado de vistas con layout (header + nav + footer),
 * redirecciones, respuestas JSON para APIs, manejo de peticiones POST y mensajes flash de sesión.
 */

abstract class Controller {

    /**
     * Renderiza una vista dentro del layout estándar (header + nav + contenido + footer)
     *
     * @param string $viewName Nombre de la vista (ej. 'ventas/index' o 'ventas.index')
     * @param array $data Datos a extraer en el ámbito de la vista
     * @param bool $useLayout Si se debe encapsular con el layout global
     */
    protected function view(string $viewName, array $data = [], bool $useLayout = true): void {
        // Extraer variables para hacerlas disponibles en la vista y layouts
        extract($data);

        // Permitir notación de puntos (ej. 'ventas.crear' -> 'ventas/crear')
        $normalizedPath = str_replace('.', '/', $viewName);
        $viewFile = 'views/' . $normalizedPath . '.php';

        if (!file_exists($viewFile)) {
            die("Error MVC: La vista '{$viewFile}' no existe.");
        }

        // Renderizar el contenido de la vista en un buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Si se solicita layout y existe el encabezado global, ensamblar
        if ($useLayout && file_exists('views/layout/header.php')) {
            require 'views/layout/header.php';
            if (file_exists('views/layout/nav.php')) {
                require 'views/layout/nav.php';
            }
            echo $content;
            if (file_exists('views/layout/footer.php')) {
                require 'views/layout/footer.php';
            }
        } else {
            // Renderizado directo del contenido
            echo $content;
        }
    }

    /**
     * Redirecciona a una ruta interna o externa
     *
     * @param string $url Ruta relativa al BASE_URL o URL absoluta
     */
    protected function redirect(string $url): void {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $target = $url;
        } else {
            $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            $target = $base . '/' . ltrim($url, '/');
        }

        header("Location: " . $target);
        exit;
    }

    /**
     * Emite una respuesta en formato JSON y finaliza la ejecución
     *
     * @param mixed $data Datos a serializar
     * @param int $code Código de estado HTTP (200, 201, 400, etc.)
     */
    protected function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Verifica si la petición actual fue enviada mediante el método HTTP POST
     *
     * @return bool
     */
    protected function isPost(): bool {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /**
     * Obtiene un valor sanitizado de $_POST o el array completo si $key es null
     *
     * @param string|null $key Clave en $_POST
     * @param mixed $default Valor de fallback si no existe
     * @return mixed
     */
    protected function post(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $_POST;
        }

        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = $_POST[$key];

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Almacena un mensaje flash en la sesión para ser mostrado en la siguiente petición
     *
     * @param string $type Tipo de alerta ('success', 'danger', 'warning', 'info')
     * @param string $message Texto del mensaje
     */
    protected function flash(string $type, string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message
        ];
    }

    /**
     * Recupera y limpia el mensaje flash almacenado en la sesión
     *
     * @return array|null Array con ['type' => ..., 'message' => ...] o null si no hay
     */
    protected function getFlash(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }

        return null;
    }
}
