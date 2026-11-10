<?php
/**
 * SIVE 2.0 - Modelo Venta
 *
 * Administra el registro de ventas con validación de medios de pago (efectivo, Yape y combinado),
 * detalle de líneas de producto y cálculo de totales diarios por caja.
 */

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/DetalleVenta.php';

class Venta extends Model {
    protected string $table = 'ventas';

    /**
     * Registra una venta validando la concordancia de importes y medios de pago.
     * Opcionalmente procesa sus líneas de detalle si vienen en el arreglo.
     *
     * @param array $data Contiene: caja_id, medio_pago, monto_efectivo, monto_yape, total, [detalles], [fecha_hora]
     * @return int ID de la venta registrada
     * @throws InvalidArgumentException|RuntimeException
     */
    public function crear(array $data): int {
        $cajaId = (int)($data['caja_id'] ?? 0);
        $medioPago = strtolower(trim($data['medio_pago'] ?? ''));
        $total = round((float)($data['total'] ?? 0), 2);
        $montoEfectivo = round((float)($data['monto_efectivo'] ?? 0), 2);
        $montoYape = round((float)($data['monto_yape'] ?? 0), 2);
        $fechaHora = $data['fecha_hora'] ?? date('Y-m-d H:i:s');

        if ($cajaId <= 0) {
            throw new InvalidArgumentException("Se requiere una caja válida para registrar la venta.");
        }

        if ($total <= 0) {
            throw new InvalidArgumentException("El total de la venta debe ser mayor a 0.");
        }

        if (!in_array($medioPago, ['efectivo', 'yape', 'combinado'], true)) {
            throw new InvalidArgumentException("Medio de pago no válido. Debe ser efectivo, yape o combinado.");
        }

        // Validación estricta de coherencia entre medio de pago y montos
        switch ($medioPago) {
            case 'efectivo':
                $montoEfectivo = $total;
                $montoYape = 0.00;
                break;
            case 'yape':
                $montoYape = $total;
                $montoEfectivo = 0.00;
                break;
            case 'combinado':
                $sumaPagos = round($montoEfectivo + $montoYape, 2);
                if (abs($sumaPagos - $total) > 0.01) {
                    throw new InvalidArgumentException("En pago combinado la suma de efectivo (S/{$montoEfectivo}) y Yape (S/{$montoYape}) debe ser exactamente igual al total (S/{$total}).");
                }
                break;
        }

        $tieneDetalles = !empty($data['detalles']) && is_array($data['detalles']);

        if ($tieneDetalles) {
            $this->beginTransaction();
        }

        try {
            $ventaId = (int)$this->insert([
                'caja_id'        => $cajaId,
                'fecha_hora'     => $fechaHora,
                'medio_pago'     => $medioPago,
                'monto_efectivo' => $montoEfectivo,
                'monto_yape'     => $montoYape,
                'total'          => $total,
                'anulada'        => 0
            ]);

            // Si vienen líneas de detalle, registrarlas con sus sabores
            if ($tieneDetalles) {
                $detalleModel = new DetalleVenta();
                foreach ($data['detalles'] as $item) {
                    $item['venta_id'] = $ventaId;
                    $detalleId = $detalleModel->crear($item);

                    if (!empty($item['sabores']) && is_array($item['sabores'])) {
                        $detalleModel->agregarSabores($detalleId, $item['sabores']);
                    }
                }
                $this->commit();
            }

            return $ventaId;
        } catch (Exception $e) {
            if ($tieneDetalles) {
                $this->rollBack();
            }
            throw new RuntimeException("Error al registrar la venta: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene todas las ventas del día asociadas a una caja específica
     *
     * @param int|string $cajaId
     * @return array
     */
    public function obtenerDelDia(int|string $cajaId): array {
        $sql = "SELECT v.*, 
                       (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.venta_id = v.id) AS total_items
                FROM `{$this->table}` v
                WHERE v.caja_id = :caja_id 
                ORDER BY v.fecha_hora DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una venta completa con todos sus ítems y sabores seleccionados
     *
     * @param int|string $ventaId
     * @return array|null
     */
    public function obtenerConDetalles(int|string $ventaId): ?array {
        $venta = $this->findById($ventaId);
        if (!$venta) {
            return null;
        }

        $detalleModel = new DetalleVenta();
        $venta['detalles'] = $detalleModel->obtenerPorVenta($ventaId);

        return $venta;
    }

    /**
     * Anula una venta registrada para excluirla de los totales de caja e inventario
     *
     * @param int|string $ventaId
     * @return bool
     */
    public function anular(int|string $ventaId): bool {
        return $this->update($ventaId, ['anulada' => 1]);
    }

    /**
     * Calcula la suma total de ventas activas para una caja
     *
     * @param int|string $cajaId
     * @return float
     */
    public function totalDelDia(int|string $cajaId): float {
        $sql = "SELECT COALESCE(SUM(total), 0) AS total 
                FROM `{$this->table}` 
                WHERE caja_id = :caja_id AND anulada = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        $row = $stmt->fetch();
        return round((float)($row['total'] ?? 0), 2);
    }

    /**
     * Desglosa el importe y cantidad de transacciones según medio de pago
     *
     * @param int|string $cajaId
     * @return array
     */
    public function totalPorMedioPago(int|string $cajaId): array {
        $sql = "SELECT 
                  COALESCE(SUM(monto_efectivo), 0) AS total_efectivo,
                  COALESCE(SUM(monto_yape), 0) AS total_yape,
                  COALESCE(SUM(total), 0) AS total_general,
                  COUNT(CASE WHEN medio_pago = 'efectivo' THEN 1 END) AS cant_efectivo,
                  COUNT(CASE WHEN medio_pago = 'yape' THEN 1 END) AS cant_yape,
                  COUNT(CASE WHEN medio_pago = 'combinado' THEN 1 END) AS cant_combinado,
                  COUNT(*) AS total_transacciones
                FROM `{$this->table}`
                WHERE caja_id = :caja_id AND anulada = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        $row = $stmt->fetch();

        return [
            'total_efectivo'      => round((float)($row['total_efectivo'] ?? 0), 2),
            'total_yape'          => round((float)($row['total_yape'] ?? 0), 2),
            'total_general'       => round((float)($row['total_general'] ?? 0), 2),
            'cant_efectivo'       => (int)($row['cant_efectivo'] ?? 0),
            'cant_yape'           => (int)($row['cant_yape'] ?? 0),
            'cant_combinado'      => (int)($row['cant_combinado'] ?? 0),
            'total_transacciones' => (int)($row['total_transacciones'] ?? 0)
        ];
    }
}
