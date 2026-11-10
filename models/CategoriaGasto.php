<?php
/**
 * SIVE 2.0 - Modelo CategoriaGasto
 *
 * Administra el catálogo de categorías para la clasificación de egresos de caja
 * (ej. Insumos, Envases, Limpieza, Servicios, Transporte, Varios).
 */

require_once __DIR__ . '/../core/Model.php';

class CategoriaGasto extends Model {
    protected string $table = 'categorias_gasto';

    /**
     * Obtiene el listado de categorías activas para selección en formularios
     *
     * @return array
     */
    public function obtenerActivas(): array {
        return $this->findWhere('activo = 1 ORDER BY nombre ASC');
    }

    /**
     * Registra una nueva categoría de gasto
     *
     * @param string $nombre
     * @return int ID de la categoría creada
     * @throws InvalidArgumentException
     */
    public function crear(string $nombre): int {
        $nombreLimpio = trim($nombre);
        if (empty($nombreLimpio)) {
            throw new InvalidArgumentException("El nombre de la categoría no puede estar vacío.");
        }

        return (int)$this->insert([
            'nombre' => $nombreLimpio,
            'activo' => 1
        ]);
    }

    /**
     * Alterna el estado activo/inactivo de una categoría
     *
     * @param int|string $id
     * @return bool Nuevo estado (true = activo, false = inactivo)
     * @throws RuntimeException Si la categoría no existe
     */
    public function toggleActivo(int|string $id): bool {
        $categoria = $this->findById($id);
        if (!$categoria) {
            throw new RuntimeException("Categoría de gasto con ID {$id} no encontrada.");
        }

        $nuevoEstado = $categoria['activo'] ? 0 : 1;
        $this->update($id, ['activo' => $nuevoEstado]);
        return (bool)$nuevoEstado;
    }

    /**
     * Obtiene todas las categorías ordenadas por estado y nombre
     *
     * @return array
     */
    public function obtenerTodas(): array {
        return $this->findAll('activo DESC, nombre ASC');
    }
}
