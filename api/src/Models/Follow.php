<?php

namespace Api\Models;

class Follow extends Model
{
    protected $table = 'follows';

    // ========== IMPLEMENTAÇÃO DOS MÉTODOS ABSTRATOS ==========

    /**
     * INSERT - Insere novo follow
     * Implementação específica do Follow
     */
    public function insert(array $data): int|null
    {
        $followerId = $data['follower_id'] ?? 0;
        $followingId = $data['following_id'] ?? 0;

        if ($followerId === $followingId) {
            return null; // Não pode seguir a si mesmo
        }

        $sql = "INSERT INTO {$this->table} (follower_id, following_id) VALUES (?, ?)";
        $this->db->query($sql, [$followerId, $followingId]);
        return $this->db->lastInsertId();
    }

    /**
     * UPDATE - Não aplicável para follows (registros imutáveis)
     * Implementação específica do Follow
     */
    public function update(int $id, array $data): bool
    {
        // Follows não são atualizados, apenas inseridos ou deletados
        return false;
    }

    // ========== MÉTODOS ESPECÍFICOS DO FOLLOW ==========

    /**
     * Para de seguir um usuário
     */
    public function unfollow(int $followerId, int $followingId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE follower_id = ? AND following_id = ?";
        $this->db->query($sql, [$followerId, $followingId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Verifica se está seguindo
     */
    public function isFollowing(int $followerId, int $followingId): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE follower_id = ? AND following_id = ?";
        return $this->db->fetchOne($sql, [$followerId, $followingId]) !== null;
    }

    /**
     * Lista seguidores de um usuário
     */
    public function getFollowers(int $userId, int $limit = 20): array
    {
        $sql = "SELECT u.* FROM users u
                JOIN {$this->table} f ON u.id = f.follower_id
                WHERE f.following_id = ?
                LIMIT ?";
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * Lista usuários que um usuário está seguindo
     */
    public function getFollowing(int $userId, int $limit = 20): array
    {
        $sql = "SELECT u.* FROM users u
                JOIN {$this->table} f ON u.id = f.following_id
                WHERE f.follower_id = ?
                LIMIT ?";
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * Conta seguidores
     */
    public function countFollowers(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE following_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }

    /**
     * Conta seguindo
     */
    public function countFollowing(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE follower_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }
}
