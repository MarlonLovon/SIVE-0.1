<?php
/**
 * SIVE 2.0 - Front Controller Principal
 *
 * Punto de entrada único de todas las peticiones web y API.
 * Carga la configuración, inicializa helpers, clases base del MVC,
 * registra el autoloader para modelos y controladores, y ejecuta el enrutador.
 */

// Cargar configuración global y base de datos
require_once 'config/app.php';
require_once 'config/database.php';

// Cargar helpers utilitarios
require_once 'core/helpers.php';

// Cargar arquitectura base MVC
require_once 'core/Model.php';
require_once 'core/Controller.php';
require_once 'core/Router.php';

// Autoload de modelos y controladores según convención de nombres
spl_autoload_register(function($class) {
    // Normalizar separadores en caso de subdirectorios
    $classPath = str_replace('\\', '/', $class);

    $modelFile = 'models/' . $classPath . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return;
    }

    $controllerFile = 'controllers/' . $classPath . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return;
    }
});

// Inicializar y despachar la ruta solicitada
$router = new Router();
$router->dispatch();
