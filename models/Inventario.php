<?php
/**
 * SIVE 2.0 - Modelo Inventario
 *
 * Modelo de cálculo de inventario estimado para helados y cremoladas.
 * Al no existir porciones exactas ni pesado en cada despacho, el stock se calcula mediante:
 * Stock Estimado = Σ Reposiciones (L) - Σ Consumo en Ventas (L) + Σ Ajustes/Mermas (L)
 *
 * Reglas de negocio:
 * 1. Ventas sin sabores específicos NO descuentan de ningún sabor.
 * 2. Ventas anuladas quedan excluidas del cálculo de consumo.
 * 3. En ítems con varios sabores, el volumen del vaso se distribuye equitativamente.
 */

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/Configuracion.php';

class Inventario extends Model {
    protected string $table = 'sabores';

    /**
     * Cache interna de parámetros de consumo para optimizar cálculos en lotes
     */
    private ?array $consumosCache = null;

    /**
     * Obtiene los parámetros de consumo volumétrico configurados
     */
    private function obtenerParametrosConsumo(): array {
        if ($this->consumosCache === null) {
            $configModel = new Configuracion();
            $this->consumosCache = $configModel->obtenerConsumos();
        }
        return $this->consumosCache;
    }

    /**
     * Estima el stock de todos los sabores activos (o filtrados por tipo)
     *
     * @param string|null $tipo 'helado' | 'cremolada' | null
     * @param bool $soloActivos Si true, solo evalúa sabores con activo = 1
     * @return array Lista de arreglos asociativos con métricas de stock estimado
     */
    public function estimarStock(?string $tipo = null, bool $soloActivos = true): array {
        $where = [];
        $params = [];

        if ($soloActivos) {
            $where[] = "activo = 1";
        }

        if ($tipo !== null) {
            $where[] = "tipo = :tipo";
            $params[':tipo'] = $tipo;
        }

        $sqlWhere = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $sql = "SELECT id, nombre, tipo, activo FROM `{$this->table}` {$sqlWhere} ORDER BY tipo ASC, nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $sabores = $stmt->fetchAll();

        $resultado = [];
        foreach ($sabores as $sabor) {
            $resultado[] = $this->calcularBalanceSabor(
                (int)$sabor['id'],
                $sabor['nombre'],
                $sabor['tipo'],
                (int)$sabor['activo']
            );
        }

        return $resultado;
    }

    /**
     * Estima el stock disponible de un sabor específico
     *
     * @param int|string $saborId
     * @return array|null
     */
    public function estimarStockSabor(int|string $saborId): ?array {
        $sql = "SELECT id, nombre, tipo, activo FROM `{$this->table}` WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $saborId]);
        $sabor = $stmt->fetch();

        if (!$sabor) {
            return null;
        }

        return $this->calcularBalanceSabor(
            (int)$sabor['id'],
            $sabor['nombre'],
            $sabor['tipo'],
            (int)$sabor['activo']
        );
    }

    /**
     * Calcula los litros consumidos acumulados de un sabor a partir de las ventas registradas
     *
     * @param int|string $saborId
     * @return float Litros consumidos
     */
    public function calcularConsumoVentas(int|string $saborId): float {
        // Obtenemos los ítems de venta donde se eligió este sabor (ventas no anuladas)
        $sql = "SELECT 
                  dv.tipo_producto,
                  dv.tamano_vaso,
                  dv.num_bolas,
                  dv.cantidad,
                  (SELECT COUNT(*) 
                   FROM detalle_venta_sabores dvs2 
                   WHERE dvs2.detalle_venta_id = dv.id) AS total_sabores_item
                FROM detalle_venta_sabores dvs
                INNER JOIN detalle_ventas dv ON dvs.detalle_venta_id = dv.id
                INNER JOIN ventas v ON dv.venta_id = v.id
                WHERE dvs.sabor_id = :sabor_id 
                  AND v.anulada = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sabor_id' => $saborId]);
        $filas = $stmt->fetchAll();

        if (empty($filas)) {
            return 0.000;
        }

        $consumos = $this->obtenerParametrosConsumo();
        $consumoBola = (float)($consumos['consumo_estimado_bola_litros'] ?? 0.05);
        $consumoCremolada6 = (float)($consumos['consumo_estimado_cremolada_6oz_litros'] ?? 0.18);
        $consumoCremolada12 = (float)($consumos['consumo_estimado_cremolada_12oz_litros'] ?? 0.35);

        $totalConsumido = 0.0;

        foreach ($filas as $item) {
            $numSabores = max(1, (int)$item['total_sabores_item']);
            $tipo = $item['tipo_producto'];
            $tamano = strtolower(trim($item['tamano_vaso']));
            $cantidad = (int)$item['cantidad'];
            $bolas = (int)$item['num_bolas'];

            if ($tipo === 'helado') {
                if ($tamano === 'litro' || $tamano === '1l' || $tamano === '1 litro') {
                    $litrosLinea = 1.000 * $cantidad;
                } else {
                    $litrosLinea = $bolas * $cantidad * $consumoBola;
                }
            } else { // cremolada
                if ($tamano === '12oz' || strpos($tamano, '12') !== false) {
                    $litrosLinea = $consumoCremolada12 * $cantidad;
                } else {
                    $litrosLinea = $consumoCremolada6 * $cantidad;
                }
            }

            // Distribuir equitativamente el volumen del vaso entre los sabores elegidos
            $totalConsumido += ($litrosLinea / $numSabores);
        }

        return round($totalConsumido, 3);
    }

    /**
     * Función auxiliar para consolidar reposiciones, consumos, ajustes y balance final de un sabor
     */
    private function calcularBalanceSabor(int $saborId, string $nombre, string $tipo, int $activo): array {
        // 1. Litros repuestos acumulados
        $sqlRep = "SELECT COALESCE(SUM(litros), 0) AS total_repuesto 
                   FROM reposiciones 
                   WHERE sabor_id = :sabor_id";
        $stmtRep = $this->db->prepare($sqlRep);
        $stmtRep->execute([':sabor_id' => $saborId]);
        $rowRep = $stmtRep->fetch();
        $litrosRepuestos = round((float)($rowRep['total_repuesto'] ?? 0), 3);

        // 2. Litros consumidos en ventas
        $litrosConsumidos = $this->calcularConsumoVentas($saborId);

        // 3. Litros ajustados manualmente (mermas negativas o adiciones positivas)
        $sqlAj = "SELECT COALESCE(SUM(litros_ajuste), 0) AS total_ajuste 
                  FROM ajustes_inventario 
                  WHERE sabor_id = :sabor_id";
        $stmtAj = $this->db->prepare($sqlAj);
        $stmtAj->execute([':sabor_id' => $saborId]);
        $rowAj = $stmtAj->fetch();
        $litrosAjustados = round((float)($rowAj['total_ajuste'] ?? 0), 3);

        // Balance estimado final: reposiciones - consumo + ajustes
        $stockEstimado = round($litrosRepuestos - $litrosConsumidos + $litrosAjustados, 3);

        // Capacidad del envase según tipo: 5L helado (bandeja), 7L cremolada (balde)
        $consumos = $this->obtenerParametrosConsumo();
        $capacidadEnvase = $tipo === 'helado' 
            ? (float)($consumos['litros_bandeja_helado'] ?? 5.0) 
            : (float)($consumos['litros_balde_cremolada'] ?? 7.0);

        return [
            'sabor_id'          => $saborId,
            'nombre'            => $nombre,
            'tipo'              => $tipo,
            'activo'            => $activo,
            'litros_repuestos'  => $litrosRepuestos,
            'litros_consumidos' => $litrosConsumidos,
            'litros_ajustados'  => $litrosAjustados,
            'stock_estimado'    => $stockEstimado,
            'capacidad_envase'  => $capacidadEnvase
        ];
    }
}
