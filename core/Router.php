<?php
/**
 * SIVE 2.0 - Enrutador MVC Principal
 *
 * Procesa la URL amigable proveniente de $_GET['url'] (.htaccess),
 * resuelve el controlador, acción y parámetros, e invoca el método correspondiente.
 */

class Router {
    protected string $defaultController = 'DashboardController';
    protected string $defaultAction = 'index';

    /**
     * Procesa la petición y ejecuta el controlador/acción correspondiente
     */
    public function dispatch(): void {
        $url = $this->parseUrl();

        // Determinar nombre del controlador
        $controllerName = $this->defaultController;
        $actionName = $this->defaultAction;
        $params = [];

        if (!empty($url[0])) {
            $rawController = $url[0];
            $controllerClass = $this->resolveControllerClass($rawController);

            if ($controllerClass) {
                $controllerName = $controllerClass;
                array_shift($url); // Remover controlador del array

                // Determinar acción
                if (!empty($url[0])) {
                    $rawAction = $url[0];
                    // Convertir formato kebab-case a camelCase (ej. crear-venta -> crearVenta)
                    $actionCandidate = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rawAction))));
                    $actionName = $actionCandidate;
                    array_shift($url); // Remover acción del array
                }

                // El resto son parámetros
                $params = $url ? array_values($url) : [];
            } else {
                // Controlador no encontrado
                $this->handleNotFound("Controlador no encontrado para la ruta solicitada: '{$rawController}'");
                return;
            }
        }

        // Verificar si la clase del controlador existe
        if (!class_exists($controllerName)) {
            $this->handleNotFound("La clase '{$controllerName}' no existe o no pudo ser cargada.");
            return;
        }

        $controllerInstance = new $controllerName();

        // Verificar si el método existe en el controlador
        if (!method_exists($controllerInstance, $actionName) || !is_callable([$controllerInstance, $actionName])) {
            $this->handleNotFound("La acción '{$actionName}' no existe en el controlador '{$controllerName}'.");
            return;
        }

        // Ejecutar la acción pasando los parámetros
        try {
            call_user_func_array([$controllerInstance, $actionName], $params);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Limpia y divide la URL en segmentos
     *
     * @return array
     */
    protected function parseUrl(): array {
        if (isset($_GET['url']) && trim($_GET['url']) !== '') {
            $cleanUrl = filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL);
            return explode('/', $cleanUrl);
        }
        return [];
    }

    /**
     * Resuelve el nombre de la clase del controlador soportando:
     * - Coincidencia directa (ej. 'api' -> 'ApiController', 'dashboard' -> 'DashboardController')
     * - Plural a singular (ej. 'ventas' -> 'VentaController')
     * - Singular a plural (ej. 'caja' -> 'CajasController' o 'CajaController')
     * - Formato compuesto kebab-case (ej. 'detalle-ventas' -> 'DetalleVentaController')
     *
     * @param string $rawController
     * @return string|null
     */
    protected function resolveControllerClass(string $rawController): ?string {
        // Normalizar a CamelCase (ej. 'detalle-ventas' -> 'DetalleVentas')
        $baseName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($rawController))));

        $candidates = [
            $baseName . 'Controller',
        ];

        // Si termina en 's', probar forma singular (ej. Ventas -> Venta, Cajas -> Caja, Sabores -> Sabor)
        if (str_ends_with($baseName, 'es') && strlen($baseName) > 2) {
            $candidates[] = substr($baseName, 0, -2) . 'Controller';
        }
        if (str_ends_with($baseName, 's') && strlen($baseName) > 1) {
            $candidates[] = substr($baseName, 0, -1) . 'Controller';
        }

        // También probar forma plural si se ingresó en singular
        $candidates[] = $baseName . 'sController';

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Maneja errores 404 de manera elegante (tanto en HTML como JSON si es API)
     *
     * @param string $detail
     */
    protected function handleNotFound(string $detail = ''): void {
        http_response_code(404);

        $isApi = (isset($_GET['url']) && str_starts_with($_GET['url'], 'api'))
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => '404 Not Found',
                'message' => $detail ?: 'El recurso solicitado no fue encontrado.'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // Si existe una vista personalizada de error 404, usarla
        if (file_exists('views/errors/404.php')) {
            $data = ['detail' => $detail];
            extract($data);
            include 'views/errors/404.php';
            exit;
        }

        // Página 404 fallback con estética moderna y enlace al inicio
        $homeUrl = defined('BASE_URL') ? BASE_URL : '/';
        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada | ' . (defined('APP_NAME') ? APP_NAME : 'SIVE 2.0') . '</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; }
        body { background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: white; max-width: 520px; width: 100%; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); text-align: center; border: 1px solid #e2e8f0; }
        .badge { display: inline-block; background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: 14px; padding: 6px 16px; border-radius: 9999px; margin-bottom: 20px; letter-spacing: 0.05em; }
        h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .detail { background: #f1f5f9; padding: 12px; border-radius: 8px; font-size: 13px; color: #475569; font-family: monospace; margin-bottom: 24px; word-break: break-all; }
        .btn { display: inline-flex; align-items: center; justify-content: center; background: #0284c7; color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 12px 24px; border-radius: 10px; transition: background 0.2s ease; box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.2); }
        .btn:hover { background: #0369a1; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">ERROR 404</div>
        <h1>Página no encontrada</h1>
        <p>La dirección que intentas abrir no existe o ha sido movida.</p>
        ' . ($detail ? '<div class="detail">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</div>' : '') . '
        <a href="' . htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') . '" class="btn">Volver al Panel Principal</a>
    </div>
</body>
</html>';
        exit;
    }

    /**
     * Maneja excepciones no capturadas durante la ejecución
     *
     * @param Throwable $e
     */
    protected function handleException(Throwable $e): void {
        http_response_code(500);
        error_log("Error en SIVE 2.0: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());

        $isApi = (isset($_GET['url']) && str_starts_with($_GET['url'], 'api'))
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => '500 Internal Server Error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error del Sistema | SIVE 2.0</title>
    <style>
        body { font-family: sans-serif; background: #fff1f2; color: #881337; padding: 40px; }
        .box { background: white; max-width: 700px; margin: 0 auto; padding: 30px; border-radius: 12px; border: 1px solid #fecdd3; }
        h2 { color: #be123c; margin-bottom: 12px; }
        pre { background: #fdf2f8; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 13px; color: #9d174d; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Se produjo un error en la aplicación</h2>
        <p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>
        <p><small>' . htmlspecialchars($e->getFile() . ' (línea ' . $e->getLine() . ')', ENT_QUOTES, 'UTF-8') . '</small></p>
    </div>
</body>
</html>';
        exit;
    }
}
