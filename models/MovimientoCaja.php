<?php
/**
 * SIVE 2.0 - Modelo MovimientoCaja
 *
 * Registra y totaliza las salidas de dinero operativas de la caja (gastos menores,
 * retiros de efectivo del propietario y pagos de reposiciones) clasificadas por medio de pago.
 */

require_once __DIR__ . '/../core/Model.php';

class MovimientoCaja extends Model {
    protected string $table = 'movimientos_caja';

    /**
     * Registra un nuevo movimiento en la caja
     *
     * @param array $data Contiene: caja_id, tipo, monto, medio_pago, categoria_gasto_id, descripcion, [fecha_hora]
     * @return int ID del movimiento creado
     * @throws InvalidArgumentException
     */
    public function crear(array $data): int {
        $cajaId = (int)($data['caja_id'] ?? 0);
        $tipo = strtolower(trim($data['tipo'] ?? ''));
        $monto = round((float)($data['monto'] ?? 0), 2);
        $medioPago = strtolower(trim($data['medio_pago'] ?? ''));
        $categoriaId = !empty($data['categoria_gasto_id']) ? (int)$data['categoria_gasto_id'] : null;
        $descripcion = trim($data['descripcion'] ?? '');
        $fechaHora = !empty($data['fecha_hora']) ? $data['fecha_hora'] : date('Y-m-d H:i:s');

        if ($cajaId <= 0) {
            throw new InvalidArgumentException("Debe asociar el movimiento a una caja válida.");
        }

        if (!in_array($tipo, ['gasto', 'retiro', 'reposicion'], true)) {
            throw new InvalidArgumentException("Tipo de movimiento inválido. Debe ser 'gasto', 'retiro' o 'reposicion'.");
        }

        if ($monto <= 0) {
            throw new InvalidArgumentException("El monto del movimiento debe ser mayor a 0.");
        }

        if (!in_array($medioPago, ['efectivo', 'yape'], true)) {
            throw new InvalidArgumentException("Medio de pago inválido. Debe ser 'efectivo' o 'yape'.");
        }

        return (int)$this->insert([
            'caja_id'            => $cajaId,
            'tipo'               => $tipo,
            'monto'              => $monto,
            'medio_pago'         => $medioPago,
            'categoria_gasto_id' => $categoriaId,
            'descripcion'        => !empty($descripcion) ? $descripcion : null,
            'fecha_hora'         => $fechaHora
        ]);
    }

    /**
     * Obtiene todos los movimientos registrados para una caja, incluyendo el nombre de la categoría
     *
     * @param int|string $cajaId
     * @return array
     */
    public function obtenerPorCaja(int|string $cajaId): array {
        $sql = "SELECT mc.*, cg.nombre AS categoria_nombre 
                FROM `{$this->table}` mc 
                LEFT JOIN `categorias_gasto` cg ON mc.categoria_gasto_id = cg.id 
                WHERE mc.caja_id = :caja_id 
                ORDER BY mc.fecha_hora DESC, mc.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula la suma de todos los egresos en efectivo (gastos, retiros y reposiciones) de una caja
     *
     * @param int|string $cajaId
     * @return float
     */
    public function totalGastosEfectivo(int|string $cajaId): float {
        $sql = "SELECT COALESCE(SUM(monto), 0) AS total_efectivo 
                FROM `{$this->table}` 
                WHERE caja_id = :caja_id 
                  AND medio_pago = 'efectivo' 
                  AND tipo IN ('gasto', 'retiro', 'reposicion')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        $row = $stmt->fetch();
        return round((float)($row['total_efectivo'] ?? 0), 2);
    }

    /**
     * Calcula la suma de todos los gastos o egresos registrados a través de cuenta Yape en una caja
     *
     * @param int|string $cajaId
     * @return float
     */
    public function totalGastosYape(int|string $cajaId): float {
        $sql = "SELECT COALESCE(SUM(monto), 0) AS total_yape 
                FROM `{$this->table}` 
                WHERE caja_id = :caja_id 
                  AND medio_pago = 'yape'
                  AND tipo IN ('gasto', 'retiro', 'reposicion')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        $row = $stmt->fetch();
        return round((float)($row['total_yape'] ?? 0), 2);
    }
}
