<?php
/**
 * SIVE 2.0 - Configuración y Conexión a Base de Datos (PDO Singleton)
 */

// Configuración Local (XAMPP / WampServer / Laragon)
define('DB_HOST', 'localhost');
define('DB_NAME', 'sive_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración Cloud / Producción (descomentar y completar según el proveedor)
// define('DB_HOST', 'your-cloud-host.com');
// define('DB_NAME', 'sive_db');
// define('DB_USER', 'your_user');
// define('DB_PASS', 'your_password');
// define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    /**
     * Constructor privado para prevenir instanciación directa
     */
    private function __construct() {}

    /**
     * Prevenir clonación de la instancia
     */
    private function __clone() {}

    /**
     * Obtiene la instancia única de conexión PDO
     *
     * @return PDO
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Registrar error de conexión seguro
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                die("Error al conectar con la base de datos de SIVE 2.0. Verifique la configuración en config/database.php.");
            }
        }
        return self::$instance;
    }
}
