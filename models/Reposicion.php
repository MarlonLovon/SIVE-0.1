<?php
/**
 * SIVE 2.0 - Modelo Reposicion
 *
 * Administra el ingreso de bandejas de helado (5 L) y baldes de cremolada (7 L).
 * Si la reposición tiene costo asociado, registra atómicamente el egreso en movimientos_caja
 * asegurando la relación 1:1 mediante clave foránea única para evitar doble contabilización.
 */

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/Caja.php';
require_once __DIR__ . '/Sabor.php';

class Reposicion extends Model {
    protected string $table = 'reposiciones';

    /**
     * Registra una nueva reposición de producto. Si tiene costo > 0, crea
     * automáticamente el movimiento en la caja activa dentro de una transacción.
     *
     * @param array $data Contiene: sabor_id, tipo, litros, fecha, costo, medio_pago, [caja_id], [observaciones]
     * @return int ID de la reposición creada
     * @throws InvalidArgumentException|RuntimeException
     */
    public function crear(array $data): int {
        $saborId = (int)($data['sabor_id'] ?? 0);
        $tipo = trim($data['tipo'] ?? '');
        $costo = round((float)($data['costo'] ?? 0), 2);
        $medioPago = !empty($data['medio_pago']) ? strtolower(trim($data['medio_pago'])) : 'efectivo';
        $fecha = !empty($data['fecha']) ? trim($data['fecha']) : date('Y-m-d');
        $observaciones = !empty($data['observaciones']) ? trim($data['observaciones']) : null;

        if ($saborId <= 0) {
            throw new InvalidArgumentException("Debe seleccionar un sabor válido para la reposición.");
        }

        if (!in_array($tipo, ['bandeja_helado', 'balde_cremolada'], true)) {
            throw new InvalidArgumentException("Tipo de reposición inválido. Debe ser 'bandeja_helado' o 'balde_cremolada'.");
        }

        // Definir volumen estándar por defecto si no viene indicado: 5L helado, 7L cremolada
        $litros = isset($data['litros']) && (float)$data['litros'] > 0
            ? (float)$data['litros']
            : ($tipo === 'bandeja_helado' ? 5.00 : 7.00);

        // Obtener datos del sabor para la descripción del movimiento
        $saborModel = new Sabor();
        $sabor = $saborModel->findById($saborId);
        $nombreSabor = $sabor ? $sabor['nombre'] : "ID #{$saborId}";

        $movimientoId = null;

        if ($costo > 0) {
            $this->beginTransaction();

            try {
                // Determinar caja a la que se cargará el costo
                $cajaId = (int)($data['caja_id'] ?? 0);
                if ($cajaId <= 0) {
                    $cajaModel = new Caja();
                    $cajaHoy = $cajaModel->obtenerHoy();
                    if ($cajaHoy && $cajaHoy['estado'] === 'abierta') {
                        $cajaId = (int)$cajaHoy['id'];
                    } else {
                        // Buscar la última caja abierta
                        $ultimaAbierta = $cajaModel->findOneWhere("estado = 'abierta'", []);
                        if ($ultimaAbierta) {
                            $cajaId = (int)$ultimaAbierta['id'];
                        } else {
                            // Si no hay caja abierta hoy, buscar caja de la fecha o última registrada
                            $cajaFecha = $cajaModel->findOneWhere('fecha = :fecha', [':fecha' => $fecha]);
                            $cajaId = $cajaFecha ? (int)$cajaFecha['id'] : 0;
                        }
                    }
                }

                if ($cajaId <= 0) {
                    throw new RuntimeException("No hay una caja disponible para registrar el egreso del costo de reposición (S/ {$costo}). Abra una caja primero.");
                }

                if (!in_array($medioPago, ['efectivo', 'yape'], true)) {
                    $medioPago = 'efectivo';
                }

                $tipoEtiqueta = $tipo === 'bandeja_helado' ? 'Bandeja Helado' : 'Balde Cremolada';
                $descripcion = "Reposición {$tipoEtiqueta}: {$nombreSabor} ({$litros}L)";

                // Insertar movimiento de caja
                $sqlMov = "INSERT INTO `movimientos_caja` 
                           (`caja_id`, `tipo`, `monto`, `medio_pago`, `categoria_gasto_id`, `descripcion`, `fecha_hora`) 
                           VALUES (:caja_id, 'reposicion', :monto, :medio_pago, NULL, :descripcion, :fecha_hora)";
                $stmtMov = $this->db->prepare($sqlMov);
                $stmtMov->execute([
                    ':caja_id'     => $cajaId,
                    ':monto'       => $costo,
                    ':medio_pago'  => $medioPago,
                    ':descripcion' => $descripcion,
                    ':fecha_hora'  => date('Y-m-d H:i:s')
                ]);
                $movimientoId = (int)$this->db->lastInsertId();

                // Insertar reposición vinculada al movimiento
                $reposicionId = (int)$this->insert([
                    'sabor_id'           => $saborId,
                    'tipo'               => $tipo,
                    'litros'             => $litros,
                    'fecha'              => $fecha,
                    'costo'              => $costo,
                    'medio_pago'         => $medioPago,
                    'movimiento_caja_id' => $movimientoId,
                    'observaciones'      => $observaciones
                ]);

                $this->commit();
                return $reposicionId;
            } catch (Exception $e) {
                $this->rollBack();
                throw new RuntimeException("Error al procesar la reposición con costo: " . $e->getMessage(), 0, $e);
            }
        }

        // Si costo == 0, no genera egreso en caja
        return (int)$this->insert([
            'sabor_id'           => $saborId,
            'tipo'               => $tipo,
            'litros'             => $litros,
            'fecha'              => $fecha,
            'costo'              => 0.00,
            'medio_pago'         => null,
            'movimiento_caja_id' => null,
            'observaciones'      => $observaciones
        ]);
    }

    /**
     * Obtiene las reposiciones de una fecha (por defecto hoy) con información del sabor
     *
     * @param string|null $fecha YYYY-MM-DD
     * @return array
     */
    public function obtenerDelDia(?string $fecha = null): array {
        $fechaConsulta = $fecha ?: date('Y-m-d');
        $sql = "SELECT r.*, s.nombre AS sabor_nombre, s.tipo AS sabor_tipo,
                       mc.medio_pago AS movimiento_medio_pago
                FROM `{$this->table}` r
                INNER JOIN `sabores` s ON r.sabor_id = s.id
                LEFT JOIN `movimientos_caja` mc ON r.movimiento_caja_id = mc.id
                WHERE r.fecha = :fecha
                ORDER BY r.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':fecha' => $fechaConsulta]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el listado de todas las reposiciones registradas
     *
     * @param int $limit
     * @return array
     */
    public function obtenerTodas(int $limit = 50): array {
        $limit = max(1, $limit);
        $sql = "SELECT r.*, s.nombre AS sabor_nombre, s.tipo AS sabor_tipo,
                       mc.medio_pago AS movimiento_medio_pago
                FROM `{$this->table}` r
                INNER JOIN `sabores` s ON r.sabor_id = s.id
                LEFT JOIN `movimientos_caja` mc ON r.movimiento_caja_id = mc.id
                ORDER BY r.fecha DESC, r.id DESC
                LIMIT {$limit}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el historial de reposiciones de un sabor particular
     *
     * @param int|string $saborId
     * @return array
     */
    public function obtenerPorSabor(int|string $saborId): array {
        $sql = "SELECT r.*, s.nombre AS sabor_nombre, s.tipo AS sabor_tipo
                FROM `{$this->table}` r
                INNER JOIN `sabores` s ON r.sabor_id = s.id
                WHERE r.sabor_id = :sabor_id
                ORDER BY r.fecha DESC, r.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sabor_id' => $saborId]);
        return $stmt->fetchAll();
    }
}
