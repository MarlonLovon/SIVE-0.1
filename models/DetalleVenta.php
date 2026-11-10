<?php
/**
 * SIVE 2.0 - Modelo DetalleVenta
 *
 * Administra las líneas individuales de cada venta, la vinculación N:M de sabores elegidos
 * y la estimación volumétrica de litros consumidos por presentación.
 */

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/Configuracion.php';

class DetalleVenta extends Model {
    protected string $table = 'detalle_ventas';

    /**
     * Registra una línea de detalle en una venta
     *
     * @param array $data Contiene: venta_id, tipo_producto, tamano_vaso, num_bolas, cantidad, precio_unitario, subtotal
     * @return int ID de la línea de detalle insertada
     * @throws InvalidArgumentException
     */
    public function crear(array $data): int {
        $ventaId = (int)($data['venta_id'] ?? 0);
        $tipoProducto = strtolower(trim($data['tipo_producto'] ?? ''));
        $tamanoVaso = trim($data['tamano_vaso'] ?? '');
        $numBolas = max(1, (int)($data['num_bolas'] ?? 1));
        $cantidad = max(1, (int)($data['cantidad'] ?? 1));
        $precioUnitario = round((float)($data['precio_unitario'] ?? 0), 2);
        $subtotal = isset($data['subtotal']) ? round((float)$data['subtotal'], 2) : round($precioUnitario * $cantidad, 2);

        if ($ventaId <= 0) {
            throw new InvalidArgumentException("ID de venta inválido para el detalle.");
        }

        if (!in_array($tipoProducto, ['helado', 'cremolada'], true)) {
            throw new InvalidArgumentException("Tipo de producto debe ser 'helado' o 'cremolada'.");
        }

        if (empty($tamanoVaso)) {
            throw new InvalidArgumentException("Debe especificar la presentación o tamaño del vaso.");
        }

        return (int)$this->insert([
            'venta_id'        => $ventaId,
            'tipo_producto'   => $tipoProducto,
            'tamano_vaso'     => $tamanoVaso,
            'num_bolas'       => $numBolas,
            'cantidad'        => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal'        => $subtotal
        ]);
    }

    /**
     * Vincula los sabores seleccionados a una línea de detalle (para deducción de inventario)
     *
     * @param int|string $detalleId
     * @param array $saborIds Arreglo de IDs de la tabla sabores
     * @return bool
     */
    public function agregarSabores(int|string $detalleId, array $saborIds): bool {
        if (empty($saborIds)) {
            return true;
        }

        $sql = "INSERT INTO `detalle_venta_sabores` (`detalle_venta_id`, `sabor_id`) 
                VALUES (:detalle_id, :sabor_id)";
        $stmt = $this->db->prepare($sql);

        foreach ($saborIds as $saborId) {
            $idLimpio = (int)$saborId;
            if ($idLimpio > 0) {
                $stmt->execute([
                    ':detalle_id' => (int)$detalleId,
                    ':sabor_id'   => $idLimpio
                ]);
            }
        }

        return true;
    }

    /**
     * Obtiene todos los detalles de una venta, incluyendo la lista de sabores elegidos
     *
     * @param int|string $ventaId
     * @return array
     */
    public function obtenerPorVenta(int|string $ventaId): array {
        $sql = "SELECT dv.* 
                FROM `{$this->table}` dv
                WHERE dv.venta_id = :venta_id 
                ORDER BY dv.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':venta_id' => $ventaId]);
        $detalles = $stmt->fetchAll();

        if (empty($detalles)) {
            return [];
        }

        $sqlSabores = "SELECT s.id, s.nombre, s.tipo 
                       FROM `detalle_venta_sabores` dvs
                       INNER JOIN `sabores` s ON dvs.sabor_id = s.id
                       WHERE dvs.detalle_venta_id = :detalle_id
                       ORDER BY s.nombre ASC";
        $stmtSabores = $this->db->prepare($sqlSabores);

        foreach ($detalles as &$item) {
            $stmtSabores->execute([':detalle_id' => $item['id']]);
            $sabores = $stmtSabores->fetchAll();
            $item['sabores'] = $sabores;

            $nombres = array_column($sabores, 'nombre');
            $item['sabores_texto'] = !empty($nombres) ? implode(', ', $nombres) : 'Sin sabor específico';
        }

        return $detalles;
    }

    /**
     * Estima el consumo en litros derivado de una línea de venta particular,
     * basándose en las variables configuradas del negocio.
     *
     * @param int|string $detalleId
     * @return float Litros consumidos estimados
     */
    public function calcularConsumo(int|string $detalleId): float {
        $detalle = $this->findById($detalleId);
        if (!$detalle) {
            return 0.000;
        }

        $configModel = new Configuracion();
        $consumos = $configModel->obtenerConsumos();

        $tipo = $detalle['tipo_producto'];
        $tamano = strtolower(trim($detalle['tamano_vaso']));
        $cantidad = (int)$detalle['cantidad'];
        $numBolas = (int)$detalle['num_bolas'];

        $litrosPorBola = (float)($consumos['consumo_estimado_bola_litros'] ?? 0.05);
        $litrosCremolada6oz = (float)($consumos['consumo_estimado_cremolada_6oz_litros'] ?? 0.18);
        $litrosCremolada12oz = (float)($consumos['consumo_estimado_cremolada_12oz_litros'] ?? 0.35);

        if ($tipo === 'helado') {
            if ($tamano === 'litro' || $tamano === '1l' || $tamano === '1 litro') {
                return round(1.0 * $cantidad, 3);
            }
            return round($numBolas * $cantidad * $litrosPorBola, 3);
        }

        if ($tipo === 'cremolada') {
            if ($tamano === '12oz' || strpos($tamano, '12') !== false) {
                return round($litrosCremolada12oz * $cantidad, 3);
            }
            return round($litrosCremolada6oz * $cantidad, 3);
        }

        return 0.000;
    }
}
