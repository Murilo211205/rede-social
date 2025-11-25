<?php

namespace Api\Models;

class Like extends Model
{
    protected $table = 'likes';

    // ========== IMPLEMENTAÇÃO DOS MÉTODOS ABSTRATOS ==========

    /**
     * INSERT - Insere novo like
     * Implementação específica do Like
     */
    public function insert(array $data): int|null
    {
        $sql = "INSERT INTO {$this->table} (user_id, post_id, comment_id) VALUES (?, ?, ?)";
        $this->db->query($sql, [
            $data['user_id'] ?? 0,
            $data['post_id'] ?? null,
            $data['comment_id'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * UPDATE - Não aplicável para likes (registros imutáveis)
     * Implementação específica do Like
     */
    public function update(int $id, array $data): bool
    {
        // Likes não são atualizados, apenas inseridos ou deletados
        return false;
    }

    // ========== MÉTODOS ESPECÍFICOS DO LIKE ==========

    /**
     * Remove like de um post
     */
    public function unlikePost(int $userId, int $postId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND post_id = ?";
        $this->db->query($sql, [$userId, $postId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Remove like de um comentário
     */
    public function unlikeComment(int $userId, int $commentId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND comment_id = ?";
        $this->db->query($sql, [$userId, $commentId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Verifica se usuário curtiu um post
     */
    public function hasLikedPost(int $userId, int $postId): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE user_id = ? AND post_id = ?";
        return $this->db->fetchOne($sql, [$userId, $postId]) !== null;
    }

    /**
     * Verifica se usuário curtiu um comentário
     */
    public function hasLikedComment(int $userId, int $commentId): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE user_id = ? AND comment_id = ?";
        return $this->db->fetchOne($sql, [$userId, $commentId]) !== null;
    }

    /**
     * Conta likes de um post
     */
    public function countPostLikes(int $postId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE post_id = ?";
        $result = $this->db->fetchOne($sql, [$postId]);
        return $result['total'] ?? 0;
    }

    /**
     * Conta likes de um comentário
     */
    public function countCommentLikes(int $commentId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE comment_id = ?";
        $result = $this->db->fetchOne($sql, [$commentId]);
        return $result['total'] ?? 0;
    }
}
