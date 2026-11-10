<?php
/**
 * SIVE 2.0 - Modelo Configuracion
 *
 * Administra los parámetros globales del sistema, incluyendo precios vigentes,
 * estimaciones de consumo por porción/vaso y capacidades de recipientes.
 */

require_once __DIR__ . '/../core/Model.php';

class Configuracion extends Model {
    protected string $table = 'configuracion';

    /**
     * Obtiene el valor de una clave de configuración específica
     *
     * @param string $clave
     * @return string|null
     */
    public function obtenerValor(string $clave): ?string {
        $sql = "SELECT valor FROM `{$this->table}` WHERE clave = :clave LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':clave' => $clave]);
        $row = $stmt->fetch();
        return $row ? (string)$row['valor'] : null;
    }

    /**
     * Obtiene todos los parámetros de precios configurados en el sistema
     *
     * @return array Mapa de clave de precio => valor numérico float
     */
    public function obtenerPrecios(): array {
        $sql = "SELECT clave, valor FROM `{$this->table}` WHERE clave LIKE 'precio_%'";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        // Valores por defecto según reglas del negocio
        $precios = [
            'precio_helado_1bola'          => 4.00,
            'precio_helado_2bolas'         => 7.00,
            'precio_helado_3bolas'         => 9.00,
            'precio_litro_helado'          => 28.00,
            'precio_cremolada_1_2_sabores' => 6.00,
            'precio_cremolada_3_sabores'   => 8.00,
        ];

        foreach ($rows as $row) {
            $precios[$row['clave']] = (float)$row['valor'];
        }

        return $precios;
    }

    /**
     * Obtiene los valores de consumos estimados y capacidades de recipientes
     *
     * @return array
     */
    public function obtenerConsumos(): array {
        $claves = [
            'litros_bandeja_helado'                 => 5.0,
            'litros_balde_cremolada'                => 7.0,
            'bolas_4oz'                             => 1,
            'bolas_6oz'                             => 2,
            'bolas_8oz'                             => 3,
            'consumo_estimado_bola_litros'          => 0.05,
            'consumo_estimado_cremolada_6oz_litros' => 0.18,
            'consumo_estimado_cremolada_12oz_litros'=> 0.35,
        ];

        $todos = $this->obtenerTodos();
        $resultado = [];

        foreach ($claves as $clave => $defecto) {
            if (isset($todos[$clave])) {
                $resultado[$clave] = is_int($defecto) ? (int)$todos[$clave] : (float)$todos[$clave];
            } else {
                $resultado[$clave] = $defecto;
            }
        }

        return $resultado;
    }

    /**
     * Actualiza o inserta el valor de una clave de configuración
     *
     * @param string $clave
     * @param mixed $valor
     * @param string|null $descripcion
     * @return bool
     */
    public function actualizarValor(string $clave, mixed $valor, ?string $descripcion = null): bool {
        $sql = "INSERT INTO `{$this->table}` (clave, valor, descripcion) 
                VALUES (:clave, :valor, :descripcion)
                ON DUPLICATE KEY UPDATE valor = :valor_up, 
                descripcion = COALESCE(:descripcion_up, descripcion)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':clave'          => $clave,
            ':valor'          => (string)$valor,
            ':descripcion'    => $descripcion,
            ':valor_up'       => (string)$valor,
            ':descripcion_up' => $descripcion
        ]);
    }

    /**
     * Obtiene todas las configuraciones como arreglo asociativo clave => valor
     *
     * @return array
     */
    public function obtenerTodos(): array {
        $sql = "SELECT clave, valor FROM `{$this->table}`";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        $configuraciones = [];
        foreach ($rows as $row) {
            $configuraciones[$row['clave']] = $row['valor'];
        }

        return $configuraciones;
    }
}
