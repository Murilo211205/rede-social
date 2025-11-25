<?php

namespace Api\Models;

class User extends Model
{
    protected $table = 'users';

    // ========== IMPLEMENTAÇÃO DOS MÉTODOS ABSTRATOS ==========

    /**
     * INSERT - Insere novo usuário
     * Implementação específica do User
     */
    public function insert(array $data): int|null
    {
        $sql = "INSERT INTO {$this->table} (username, email, password_hash, bio, avatar_url) 
                VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['username'] ?? '',
            $data['email'] ?? '',
            $data['password_hash'] ?? '',
            $data['bio'] ?? null,
            $data['avatar_url'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * UPDATE - Atualiza usuário
     * Implementação específica do User
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} 
                SET username = ?, email = ?, bio = ?, avatar_url = ? 
                WHERE id = ?";
        $this->db->query($sql, [
            $data['username'] ?? '',
            $data['email'] ?? '',
            $data['bio'] ?? null,
            $data['avatar_url'] ?? null,
            $id
        ]);
        return $this->db->affectedRows() > 0;
    }

    // ========== MÉTODOS ESPECÍFICOS DO USER ==========

    /**
     * Encontra usuário por email
     */
    public function findByEmail(string $email): array|null
    {
        return $this->findBy('email', $email);
    }

    /**
     * Encontra usuário por username
     */
    public function findByUsername(string $username): array|null
    {
        return $this->findBy('username', $username);
    }

    /**
     * Conta seguidores
     */
    public function countFollowers(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM follows WHERE following_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }

    /**
     * Conta seguindo
     */
    public function countFollowing(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM follows WHERE follower_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }

    /**
     * Busca usuários (para autocomplete)
     */
    public function search(string $query, int $limit = 10): array
    {
        $query = '%' . $query . '%';
        $sql = "SELECT id, username, avatar_url FROM {$this->table} 
                WHERE (username LIKE ? OR email LIKE ?) AND is_banned = FALSE
                ORDER BY (SELECT COUNT(*) FROM follows WHERE following_id = users.id) DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$query, $query, $limit]);
    }

    /**
     * Bane um usuário (admin)
     */
    public function ban(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET is_banned = TRUE WHERE id = ?";
        $this->db->query($sql, [$id]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Desbanir um usuário (admin)
     */
    public function unban(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET is_banned = FALSE WHERE id = ?";
        $this->db->query($sql, [$id]);
        return $this->db->affectedRows() > 0;
    }
}
