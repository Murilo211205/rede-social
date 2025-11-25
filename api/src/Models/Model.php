<?php

namespace Api\Models;

/**
 * Classe abstrata Model
 * Define métodos padrão CRUD que podem ser sobrescritos pelas classes filhas
 * 
 * Padrão de Design: Template Method
 * - Define a estrutura básica de operações CRUD
 * - Permite que subclasses sobrescrevam comportamentos específicos
 */
abstract class Model
{
    protected $db;
    protected $table;

    public function __construct()
    {
        $this->db = \Api\Utils\Database::getInstance();
    }

    // ========== MÉTODOS PADRÃO CRUD (conforme especificação) ==========

    /**
     * GET - Obtém um registro por ID
     * Método padrão que pode ser sobrescrito
     */
    public function get(int $id): array|null
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * LIST - Lista todos os registros
     * Método padrão que pode ser sobrescrito
     */
    public function list(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->fetchAll($sql);
    }

    /**
     * INSERT - Insere um novo registro
     * Método abstrato que DEVE ser implementado pelas classes filhas
     * Cada entidade tem seus próprios campos específicos
     */
    abstract public function insert(array $data): int|null;

    /**
     * UPDATE - Atualiza um registro
     * Método abstrato que DEVE ser implementado pelas classes filhas
     * Cada entidade tem seus próprios campos específicos
     */
    abstract public function update(int $id, array $data): bool;

    /**
     * DELETE - Remove um registro por ID
     * Método padrão que pode ser sobrescrito
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return $this->db->affectedRows() > 0;
    }

    // ========== MÉTODOS AUXILIARES ==========

    /**
     * Encontra por coluna e valor
     */
    public function findBy(string $column, $value): array|null
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        return $this->db->fetchOne($sql, [$value]);
    }

    /**
     * Retorna todos com filtro
     */
    public function findAllBy(string $column, $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        return $this->db->fetchAll($sql, [$value]);
    }

    /**
     * Conta registros
     */
    public function count(string $where = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $result = $this->db->fetchOne($sql);
        return $result['total'] ?? 0;
    }
}
