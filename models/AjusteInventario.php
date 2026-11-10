<?php
/**
 * SIVE 2.0 - Modelo AjusteInventario
 *
 * Registra correcciones manuales sobre el stock estimado de litros de cada sabor
 * (mermas por deshielo, degustaciones gratuitas a clientes, calibración física o roturas).
 * Valores positivos incrementan el stock y valores negativos lo reducen.
 */

require_once __DIR__ . '/../core/Model.php';

class AjusteInventario extends Model {
    protected string $table = 'ajustes_inventario';

    /**
     * Registra un nuevo ajuste de inventario para un sabor
     *
     * @param array $data Contiene: sabor_id, litros_ajuste (positivo suma, negativo resta), motivo, [fecha]
     * @return int ID del ajuste insertado
     * @throws InvalidArgumentException
     */
    public function crear(array $data): int {
        $saborId = (int)($data['sabor_id'] ?? 0);
        $litrosAjuste = round((float)($data['litros_ajuste'] ?? 0), 3);
        $motivo = trim($data['motivo'] ?? '');
        $fecha = !empty($data['fecha']) ? $data['fecha'] : date('Y-m-d H:i:s');

        if ($saborId <= 0) {
            throw new InvalidArgumentException("Debe especificar un sabor válido para el ajuste.");
        }

        if ($litrosAjuste == 0.0) {
            throw new InvalidArgumentException("La variación en litros no puede ser 0.");
        }

        if (empty($motivo)) {
            throw new InvalidArgumentException("Debe ingresar el motivo del ajuste (ej. Merma, degustación, corrección física).");
        }

        return (int)$this->insert([
            'sabor_id'      => $saborId,
            'litros_ajuste' => $litrosAjuste,
            'motivo'        => $motivo,
            'fecha'         => $fecha
        ]);
    }

    /**
     * Obtiene el historial de ajustes aplicados a un sabor específico
     *
     * @param int|string $saborId
     * @return array
     */
    public function obtenerPorSabor(int|string $saborId): array {
        $sql = "SELECT ai.*, s.nombre AS sabor_nombre, s.tipo AS sabor_tipo 
                FROM `{$this->table}` ai 
                INNER JOIN `sabores` s ON ai.sabor_id = s.id 
                WHERE ai.sabor_id = :sabor_id 
                ORDER BY ai.fecha DESC, ai.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sabor_id' => $saborId]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula la suma acumulada neta de ajustes para un sabor en litros
     * (puede ser positivo si predominaron ingresos o negativo si hubo mermas)
     *
     * @param int|string $saborId
     * @return float
     */
    public function totalAjustes(int|string $saborId): float {
        $sql = "SELECT COALESCE(SUM(litros_ajuste), 0) AS total_ajustes 
                FROM `{$this->table}` 
                WHERE sabor_id = :sabor_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sabor_id' => $saborId]);
        $row = $stmt->fetch();
        return round((float)($row['total_ajustes'] ?? 0), 3);
    }

    /**
     * Obtiene los últimos ajustes registrados en el sistema
     *
     * @param int $limit
     * @return array
     */
    public function obtenerTodos(int $limit = 50): array {
        $limit = max(1, $limit);
        $sql = "SELECT ai.*, s.nombre AS sabor_nombre, s.tipo AS sabor_tipo 
                FROM `{$this->table}` ai 
                INNER JOIN `sabores` s ON ai.sabor_id = s.id 
                ORDER BY ai.fecha DESC, ai.id DESC 
                LIMIT {$limit}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
