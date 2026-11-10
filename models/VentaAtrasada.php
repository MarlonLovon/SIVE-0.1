<?php
/**
 * SIVE 2.0 - Modelo VentaAtrasada
 *
 * Registra consolidados de ventas de fechas pasadas en las que no se utilizó el sistema
 * en tiempo real. Conserva la fecha real de venta y la fecha de registro digital,
 * calculando el total estimado según tarifas vigentes (usando S/ 6 como base para cremoladas).
 */

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/Configuracion.php';

class VentaAtrasada extends Model {
    protected string $table = 'ventas_atrasadas';

    /**
     * Calcula el importe total estimado a partir de las cantidades ingresadas y tarifas configuradas.
     * Regla de negocio: para cremoladas se aplica por defecto la tarifa menor (S/ 6) al no conocerse
     * la cantidad exacta de sabores servidos.
     *
     * @param array $data Cantidades: helados_4oz, helados_6oz, helados_8oz, cremoladas_6oz, cremoladas_12oz, litros_helado
     * @param array|null $precios Opcional: Arreglo de precios. Si es null, se cargan de Configuracion.
     * @return float Total calculado
     */
    public function calcularTotal(array $data, ?array $precios = null): float {
        if ($precios === null) {
            $configModel = new Configuracion();
            $precios = $configModel->obtenerPrecios();
        }

        $p1Bola = (float)($precios['precio_helado_1bola'] ?? 4.00);
        $p2Bolas = (float)($precios['precio_helado_2bolas'] ?? 7.00);
        $p3Bolas = (float)($precios['precio_helado_3bolas'] ?? 9.00);
        $pLitro = (float)($precios['precio_litro_helado'] ?? 28.00);
        // Regla: Para cremoladas usar precio base menor (S/ 6) por defecto
        $pCremoladaBase = (float)($precios['precio_cremolada_1_2_sabores'] ?? 6.00);
        $pCremolada12oz = (float)($precios['precio_cremolada_3_sabores'] ?? 8.00);

        $h4 = max(0, (int)($data['helados_4oz'] ?? 0));
        $h6 = max(0, (int)($data['helados_6oz'] ?? 0));
        $h8 = max(0, (int)($data['helados_8oz'] ?? 0));
        $litros = max(0, (int)($data['litros_helado'] ?? 0));
        $c6 = max(0, (int)($data['cremoladas_6oz'] ?? ($data['cremoladas'] ?? 0)));
        $c12 = max(0, (int)($data['cremoladas_12oz'] ?? 0));

        $total = ($h4 * $p1Bola)
               + ($h6 * $p2Bolas)
               + ($h8 * $p3Bolas)
               + ($litros * $pLitro)
               + ($c6 * $pCremoladaBase)
               + ($c12 * $pCremolada12oz);

        return round($total, 2);
    }

    /**
     * Registra un resumen de venta atrasada
     *
     * @param array $data Datos: fecha_venta, cantidades, total_anotado, total_calculado, notas
     * @return int ID del registro insertado
     * @throws InvalidArgumentException
     */
    public function crear(array $data): int {
        $fechaVenta = trim($data['fecha_venta'] ?? '');
        if (empty($fechaVenta) || !strtotime($fechaVenta)) {
            throw new InvalidArgumentException("Debe proporcionar una fecha de venta válida.");
        }

        $totalCalculado = isset($data['total_calculado']) 
            ? round((float)$data['total_calculado'], 2) 
            : $this->calcularTotal($data);

        $totalAnotado = isset($data['total_anotado']) && $data['total_anotado'] !== '' 
            ? round((float)$data['total_anotado'], 2) 
            : null;

        return (int)$this->insert([
            'fecha_venta'     => $fechaVenta,
            'fecha_registro'  => $data['fecha_registro'] ?? date('Y-m-d H:i:s'),
            'helados_4oz'     => max(0, (int)($data['helados_4oz'] ?? 0)),
            'helados_6oz'     => max(0, (int)($data['helados_6oz'] ?? 0)),
            'helados_8oz'     => max(0, (int)($data['helados_8oz'] ?? 0)),
            'cremoladas_6oz'  => max(0, (int)($data['cremoladas_6oz'] ?? 0)),
            'cremoladas_12oz' => max(0, (int)($data['cremoladas_12oz'] ?? 0)),
            'litros_helado'   => max(0, (int)($data['litros_helado'] ?? 0)),
            'total_calculado' => $totalCalculado,
            'total_anotado'   => $totalAnotado,
            'notas'           => !empty($data['notas']) ? trim($data['notas']) : null
        ]);
    }

    /**
     * Obtiene el listado de ventas atrasadas ordenadas por fecha más reciente
     *
     * @param int $limit
     * @return array
     */
    public function obtenerTodas(int $limit = 50): array {
        $limit = max(1, $limit);
        $sql = "SELECT * FROM `{$this->table}` 
                ORDER BY fecha_venta DESC, id DESC 
                LIMIT {$limit}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los registros de ventas atrasadas para una fecha específica
     *
     * @param string $fecha YYYY-MM-DD
     * @return array
     */
    public function obtenerPorFecha(string $fecha): array {
        return $this->findWhere('fecha_venta = :fecha ORDER BY id DESC', [
            ':fecha' => $fecha
        ]);
    }
}
