<?php
/**
 * SIVE 2.0 - Modelo Sabor
 *
 * Catálogo de sabores disponibles para helados y cremoladas.
 * Permite listar sabores activos, registrar nuevos y alternar su disponibilidad.
 */

require_once __DIR__ . '/../core/Model.php';

class Sabor extends Model {
    protected string $table = 'sabores';

    /**
     * Obtiene los sabores activos, opcionalmente filtrados por tipo
     *
     * @param string|null $tipo 'helado' | 'cremolada' | null
     * @return array
     */
    public function obtenerActivos(?string $tipo = null): array {
        if ($tipo !== null) {
            $sql = "SELECT * FROM `{$this->table}` WHERE activo = 1 AND tipo = :tipo ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tipo' => $tipo]);
            return $stmt->fetchAll();
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE activo = 1 ORDER BY tipo ASC, nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo sabor en el catálogo
     *
     * @param string $nombre Nombre del sabor (ej. Lúcuma, Chocolate)
     * @param string $tipo 'helado' o 'cremolada'
     * @return int ID del sabor creado
     * @throws InvalidArgumentException
     */
    public function crear(string $nombre, string $tipo): int {
        $nombreLimpio = trim($nombre);
        $tipoLimpio = strtolower(trim($tipo));

        if (empty($nombreLimpio)) {
            throw new InvalidArgumentException("El nombre del sabor no puede estar vacío.");
        }

        if (!in_array($tipoLimpio, ['helado', 'cremolada'], true)) {
            throw new InvalidArgumentException("El tipo de sabor debe ser 'helado' o 'cremolada'.");
        }

        return (int)$this->insert([
            'nombre' => $nombreLimpio,
            'tipo'   => $tipoLimpio,
            'activo' => 1
        ]);
    }

    /**
     * Alterna el estado activo/inactivo de un sabor
     *
     * @param int|string $id
     * @return bool Estado resultante (true = activo, false = inactivo)
     * @throws RuntimeException Si el sabor no existe
     */
    public function toggleActivo(int|string $id): bool {
        $sabor = $this->findById($id);
        if (!$sabor) {
            throw new RuntimeException("Sabor con ID {$id} no encontrado.");
        }

        $nuevoEstado = $sabor['activo'] ? 0 : 1;
        $this->update($id, ['activo' => $nuevoEstado]);
        return (bool)$nuevoEstado;
    }

    /**
     * Obtiene todos los sabores (activos e inactivos), opcionalmente filtrados por tipo
     *
     * @param string|null $tipo
     * @return array
     */
    public function obtenerTodos(?string $tipo = null): array {
        if ($tipo !== null) {
            $sql = "SELECT * FROM `{$this->table}` WHERE tipo = :tipo ORDER BY activo DESC, nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tipo' => $tipo]);
            return $stmt->fetchAll();
        }

        return $this->findAll('tipo ASC, activo DESC, nombre ASC');
    }
}
