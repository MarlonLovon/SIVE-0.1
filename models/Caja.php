<?php
/**
 * SIVE 2.0 - Modelo Caja
 *
 * Administra el flujo diario de caja: aperturas con fondo inicial, cálculo de efectivo
 * esperado (fondo + ventas efectivo - egresos efectivo), totalización de Yape y cierre de turno.
 * Permite advertir sobre cajas de días anteriores pendientes de cierre.
 */

require_once __DIR__ . '/../core/Model.php';

class Caja extends Model {
    protected string $table = 'cajas';

    /**
     * Obtiene el registro de caja para la fecha de hoy
     *
     * @return array|null
     */
    public function obtenerHoy(): ?array {
        $hoy = date('Y-m-d');
        return $this->findOneWhere('fecha = :fecha', [':fecha' => $hoy]);
    }

    /**
     * Verifica si existe una caja abierta para el día de hoy
     *
     * @return bool
     */
    public function estaAbierta(): bool {
        $hoy = date('Y-m-d');
        $caja = $this->findOneWhere('fecha = :fecha AND estado = :estado', [
            ':fecha'  => $hoy,
            ':estado' => 'abierta'
        ]);
        return $caja !== null;
    }

    /**
     * Comprueba si existe alguna caja de fechas anteriores que haya quedado sin cerrar
     *
     * @return array|null Datos de la caja anterior pendiente o null si todo está en orden
     */
    public function cajaAnteriorSinCerrar(): ?array {
        $hoy = date('Y-m-d');
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE fecha < :hoy AND estado = 'abierta' 
                ORDER BY fecha DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':hoy' => $hoy]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Abre la caja para el día de hoy con el fondo inicial proporcionado
     *
     * @param float $fondoInicial Monto inicial en efectivo para vuelto/sencillo
     * @return int ID de la caja abierta
     * @throws RuntimeException Si ya existe una caja abierta hoy
     */
    public function abrir(float $fondoInicial): int {
        $hoy = date('Y-m-d');
        $existente = $this->findOneWhere('fecha = :fecha', [':fecha' => $hoy]);

        if ($existente) {
            if ($existente['estado'] === 'abierta') {
                return (int)$existente['id'];
            }
            // Si ya existía cerrada hoy, se permite reabrir actualizando apertura y fondo
            $this->update($existente['id'], [
                'fondo_inicial'    => $fondoInicial,
                'apertura'         => date('Y-m-d H:i:s'),
                'cierre'           => null,
                'estado'           => 'abierta',
                'efectivo_esperado'=> $fondoInicial,
                'efectivo_contado' => null
            ]);
            return (int)$existente['id'];
        }

        $id = $this->insert([
            'fecha'             => $hoy,
            'fondo_inicial'     => $fondoInicial,
            'apertura'          => date('Y-m-d H:i:s'),
            'cierre'            => null,
            'efectivo_esperado' => $fondoInicial,
            'efectivo_contado'  => null,
            'total_yape'        => 0.00,
            'estado'            => 'abierta',
            'observaciones'     => null
        ]);

        return (int)$id;
    }

    /**
     * Cierra la caja activa registrando el conteo físico de efectivo y calculando diferencias
     *
     * @param float $efectivoContado Dinero físico verificado en caja
     * @param string $observaciones Notas sobre descuadres o comentarios
     * @param int|null $cajaId Opcional: ID específico de caja, por defecto la de hoy
     * @return bool
     * @throws RuntimeException Si no se encuentra caja abierta para cerrar
     */
    public function cerrar(float $efectivoContado, string $observaciones = '', ?int $cajaId = null): bool {
        if ($cajaId === null) {
            $caja = $this->obtenerHoy();
            if (!$caja || $caja['estado'] !== 'abierta') {
                // Intentar buscar la última caja abierta
                $caja = $this->findOneWhere("estado = 'abierta'", []);
            }
        } else {
            $caja = $this->findById($cajaId);
        }

        if (!$caja) {
            throw new RuntimeException("No se encontró una caja abierta para realizar el cierre.");
        }

        $id = (int)$caja['id'];
        $efectivoEsperado = $this->calcularEfectivoEsperado($id);
        $totalYape = $this->calcularTotalYape($id);

        return $this->update($id, [
            'cierre'            => date('Y-m-d H:i:s'),
            'efectivo_esperado' => $efectivoEsperado,
            'efectivo_contado'  => $efectivoContado,
            'total_yape'        => $totalYape,
            'estado'            => 'cerrada',
            'observaciones'     => trim($observaciones)
        ]);
    }

    /**
     * Calcula el efectivo esperado en caja:
     * Fondo Inicial + Ventas Efectivo - Egresos Efectivo (Gastos + Retiros + Reposiciones en efectivo)
     *
     * @param int|string $cajaId
     * @return float
     */
    public function calcularEfectivoEsperado(int|string $cajaId): float {
        $caja = $this->findById($cajaId);
        if (!$caja) {
            return 0.00;
        }

        $fondoInicial = (float)$caja['fondo_inicial'];

        // Suma de efectivo cobrado en ventas activas (no anuladas)
        $sqlVentas = "SELECT COALESCE(SUM(monto_efectivo), 0) AS total_efectivo 
                      FROM ventas 
                      WHERE caja_id = :caja_id AND anulada = 0";
        $stmtVentas = $this->db->prepare($sqlVentas);
        $stmtVentas->execute([':caja_id' => $cajaId]);
        $rowVentas = $stmtVentas->fetch();
        $ventasEfectivo = (float)($rowVentas['total_efectivo'] ?? 0);

        // Suma de salidas de dinero en efectivo (gastos, retiros y reposiciones pagadas de caja)
        $sqlEgresos = "SELECT COALESCE(SUM(monto), 0) AS total_egresos 
                       FROM movimientos_caja 
                       WHERE caja_id = :caja_id AND medio_pago = 'efectivo'";
        $stmtEgresos = $this->db->prepare($sqlEgresos);
        $stmtEgresos->execute([':caja_id' => $cajaId]);
        $rowEgresos = $stmtEgresos->fetch();
        $egresosEfectivo = (float)($rowEgresos['total_egresos'] ?? 0);

        $esperado = $fondoInicial + $ventasEfectivo - $egresosEfectivo;
        return round($esperado, 2);
    }

    /**
     * Suma los cobros efectuados mediante Yape en ventas activas
     *
     * @param int|string $cajaId
     * @return float
     */
    public function calcularTotalYape(int|string $cajaId): float {
        $sql = "SELECT COALESCE(SUM(monto_yape), 0) AS total_yape 
                FROM ventas 
                WHERE caja_id = :caja_id AND anulada = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':caja_id' => $cajaId]);
        $row = $stmt->fetch();
        return round((float)($row['total_yape'] ?? 0), 2);
    }

    /**
     * Genera un resumen completo financiero de la caja indicada
     *
     * @param int|string $cajaId
     * @return array
     */
    public function obtenerResumen(int|string $cajaId): array {
        $caja = $this->findById($cajaId);
        if (!$caja) {
            throw new RuntimeException("Caja no encontrada.");
        }

        $fondoInicial = (float)$caja['fondo_inicial'];

        // Ventas
        $sqlVentas = "SELECT 
                        COUNT(CASE WHEN anulada = 0 THEN 1 END) AS cantidad_ventas,
                        COUNT(CASE WHEN anulada = 1 THEN 1 END) AS cantidad_anuladas,
                        COALESCE(SUM(CASE WHEN anulada = 0 THEN total ELSE 0 END), 0) AS total_ventas,
                        COALESCE(SUM(CASE WHEN anulada = 0 THEN monto_efectivo ELSE 0 END), 0) AS ventas_efectivo,
                        COALESCE(SUM(CASE WHEN anulada = 0 THEN monto_yape ELSE 0 END), 0) AS ventas_yape
                      FROM ventas 
                      WHERE caja_id = :caja_id";
        $stmtV = $this->db->prepare($sqlVentas);
        $stmtV->execute([':caja_id' => $cajaId]);
        $ventasInfo = $stmtV->fetch();

        // Movimientos desglosados
        $sqlMov = "SELECT 
                     COALESCE(SUM(CASE WHEN tipo = 'gasto' AND medio_pago = 'efectivo' THEN monto ELSE 0 END), 0) AS gastos_efectivo,
                     COALESCE(SUM(CASE WHEN tipo = 'gasto' AND medio_pago = 'yape' THEN monto ELSE 0 END), 0) AS gastos_yape,
                     COALESCE(SUM(CASE WHEN tipo = 'retiro' AND medio_pago = 'efectivo' THEN monto ELSE 0 END), 0) AS retiros_efectivo,
                     COALESCE(SUM(CASE WHEN tipo = 'retiro' AND medio_pago = 'yape' THEN monto ELSE 0 END), 0) AS retiros_yape,
                     COALESCE(SUM(CASE WHEN tipo = 'reposicion' AND medio_pago = 'efectivo' THEN monto ELSE 0 END), 0) AS reposiciones_efectivo,
                     COALESCE(SUM(CASE WHEN tipo = 'reposicion' AND medio_pago = 'yape' THEN monto ELSE 0 END), 0) AS reposiciones_yape,
                     COALESCE(SUM(CASE WHEN medio_pago = 'efectivo' THEN monto ELSE 0 END), 0) AS total_egresos_efectivo,
                     COALESCE(SUM(CASE WHEN medio_pago = 'yape' THEN monto ELSE 0 END), 0) AS total_egresos_yape
                   FROM movimientos_caja 
                   WHERE caja_id = :caja_id";
        $stmtM = $this->db->prepare($sqlMov);
        $stmtM->execute([':caja_id' => $cajaId]);
        $movInfo = $stmtM->fetch();

        $efectivoEsperado = $fondoInicial + (float)$ventasInfo['ventas_efectivo'] - (float)$movInfo['total_egresos_efectivo'];
        $efectivoContado = $caja['efectivo_contado'] !== null ? (float)$caja['efectivo_contado'] : null;
        $diferencia = $efectivoContado !== null ? round($efectivoContado - $efectivoEsperado, 2) : null;

        return [
            'caja'                   => $caja,
            'id'                     => (int)$caja['id'],
            'fecha'                  => $caja['fecha'],
            'estado'                 => $caja['estado'],
            'apertura'               => $caja['apertura'],
            'cierre'                 => $caja['cierre'],
            'observaciones'          => $caja['observaciones'],
            'fondo_inicial'          => $fondoInicial,
            'total_ventas'           => round((float)$ventasInfo['total_ventas'], 2),
            'ventas_efectivo'        => round((float)$ventasInfo['ventas_efectivo'], 2),
            'ventas_yape'            => round((float)$ventasInfo['ventas_yape'], 2),
            'cantidad_ventas'        => (int)$ventasInfo['cantidad_ventas'],
            'cantidad_anuladas'      => (int)$ventasInfo['cantidad_anuladas'],
            'gastos_efectivo'        => round((float)$movInfo['gastos_efectivo'], 2),
            'gastos_yape'            => round((float)$movInfo['gastos_yape'], 2),
            'retiros_efectivo'       => round((float)$movInfo['retiros_efectivo'], 2),
            'retiros_yape'           => round((float)$movInfo['retiros_yape'], 2),
            'reposiciones_efectivo'  => round((float)$movInfo['reposiciones_efectivo'], 2),
            'reposiciones_yape'      => round((float)$movInfo['reposiciones_yape'], 2),
            'total_egresos_efectivo' => round((float)$movInfo['total_egresos_efectivo'], 2),
            'total_egresos_yape'     => round((float)$movInfo['total_egresos_yape'], 2),
            'total_gastos'           => round((float)$movInfo['gastos_efectivo'] + (float)$movInfo['gastos_yape'], 2),
            'efectivo_esperado'      => round($efectivoEsperado, 2),
            'efectivo_contado'       => $efectivoContado,
            'diferencia_efectivo'    => $diferencia,
            'total_yape'             => round((float)$ventasInfo['ventas_yape'], 2)
        ];
    }
}
