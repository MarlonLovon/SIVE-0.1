<?php
/**
 * SIVE 2.0 - Modelo Base del Sistema MVC
 *
 * Provee operaciones CRUD estándar sobre tablas MySQL utilizando PDO y
 * sentencias preparadas para prevenir inyección SQL.
 */

abstract class Model {
    /**
     * Nombre de la tabla asociada en la base de datos
     */
    protected string $table = '';

    /**
     * Instancia de conexión PDO
     */
    protected PDO $db;

    /**
     * Inicializa la conexión PDO desde el Singleton de Database
     */
    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Retorna el nombre de la tabla configurada
     */
    public function getTable(): string {
        return $this->table;
    }

    /**
     * Obtiene todos los registros de la tabla ordenados
     *
     * @param string $orderBy Cláusula de ordenamiento (ej. 'id DESC')
     * @return array Lista de registros asociativos
     */
    public function findAll(string $orderBy = 'id DESC'): array {
        $sql = "SELECT * FROM `{$this->table}` ORDER BY {$orderBy}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca un registro por su clave primaria id
     *
     * @param int|string $id
     * @return array|null Registro encontrado o null
     */
    public function findById(int|string $id): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Busca registros que cumplan con una condición WHERE específica
     *
     * @param string $conditions Cláusula WHERE sin la palabra 'WHERE' (ej. 'tipo = :tipo AND activo = :activo')
     * @param array $params Parámetros enlazados para la consulta preparada
     * @return array Lista de registros que coinciden
     */
    public function findWhere(string $conditions, array $params = []): array {
        $sql = "SELECT * FROM `{$this->table}` WHERE {$conditions}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Busca el primer registro que cumpla una condición
     *
     * @param string $conditions
     * @param array $params
     * @return array|null
     */
    public function findOneWhere(string $conditions, array $params = []): ?array {
        $sql = "SELECT * FROM `{$this->table}` WHERE {$conditions} LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Inserta un nuevo registro en la tabla
     *
     * @param array $data Mapa asociativo columna => valor
     * @return int|string ID generado por lastInsertId
     */
    public function insert(array $data): int|string {
        $columns = array_keys($data);
        $escapedColumns = array_map(fn($col) => "`" . str_replace('`', '', $col) . "`", $columns);
        $placeholders = array_map(fn($col) => ":" . preg_replace('/[^a-zA-Z0-9_]/', '_', $col), $columns);

        $sql = "INSERT INTO `{$this->table}` (" . implode(', ', $escapedColumns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);

        $params = [];
        foreach ($data as $col => $val) {
            $paramKey = ":" . preg_replace('/[^a-zA-Z0-9_]/', '_', $col);
            $params[$paramKey] = $val;
        }

        $stmt->execute($params);
        return $this->db->lastInsertId();
    }

    /**
     * Actualiza un registro existente identificado por su id
     *
     * @param int|string $id
     * @param array $data Mapa asociativo columna => nuevo valor
     * @return bool Verdadero si la ejecución fue exitosa
     */
    public function update(int|string $id, array $data): bool {
        $setClauses = [];
        $params = [':_record_id' => $id];

        foreach ($data as $col => $val) {
            $cleanCol = str_replace('`', '', $col);
            $paramKey = ":set_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $cleanCol);
            $setClauses[] = "`{$cleanCol}` = {$paramKey}";
            $params[$paramKey] = $val;
        }

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $setClauses) . " WHERE id = :_record_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Elimina un registro por su clave primaria id
     *
     * @param int|string $id
     * @return bool Verdadero si la sentencia se ejecutó correctamente
     */
    public function delete(int|string $id): bool {
        $sql = "DELETE FROM `{$this->table}` WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Ejecuta una consulta SQL personalizada con parámetros preparados
     *
     * @param string $sql Consulta SQL
     * @param array $params Parámetros enlazados
     * @return PDOStatement Sentencia ejecutada para fetch, fetchAll o rowCount
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Ejecuta una consulta SQL y retorna directamente todos los resultados como array
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function queryAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Ejecuta una consulta SQL y retorna la primera fila
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function queryOne(string $sql, array $params = []): ?array {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Cuenta el número de registros que cumplen con las condiciones dadas
     *
     * @param string $conditions Cláusula WHERE opcional
     * @param array $params Parámetros para la condición
     * @return int
     */
    public function count(string $conditions = '', array $params = []): int {
        $sql = "SELECT COUNT(*) AS total FROM `{$this->table}`";
        if (trim($conditions) !== '') {
            $sql .= " WHERE " . $conditions;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Inicia una transacción de base de datos
     */
    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }

    /**
     * Confirma la transacción actual
     */
    public function commit(): bool {
        return $this->db->commit();
    }

    /**
     * Revierte la transacción actual
     */
    public function rollBack(): bool {
        return $this->db->rollBack();
    }
}
