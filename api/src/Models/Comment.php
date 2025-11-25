<?php

namespace Api\Models;

class Comment extends Model
{
    protected $table = 'comments';

    // ========== IMPLEMENTAÇÃO DOS MÉTODOS ABSTRATOS ==========

    /**
     * INSERT - Insere novo comentário
     * Implementação específica do Comment
     */
    public function insert(array $data): int|null
    {
        $sql = "INSERT INTO {$this->table} (post_id, user_id, parent_comment_id, content) 
                VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['post_id'] ?? 0,
            $data['user_id'] ?? 0,
            $data['parent_comment_id'] ?? null,
            $data['content'] ?? ''
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * UPDATE - Atualiza comentário
     * Implementação específica do Comment
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET content = ? WHERE id = ?";
        $this->db->query($sql, [$data['content'] ?? '', $id]);
        return $this->db->affectedRows() > 0;
    }

    // ========== MÉTODOS ESPECÍFICOS DO COMMENT ==========

    /**
     * Lista comentários de um post com usuário
     */
    public function listByPost(int $postId, string $sort = 'recent'): array
    {
        $orderBy = match ($sort) {
            'oldest' => 'c.created_at ASC',
            'popular' => 'c.likes_count DESC',
            default => 'c.created_at DESC', // recent
        };

        $sql = "SELECT 
                    c.*,
                    u.username,
                    u.avatar_url
                FROM {$this->table} c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY {$orderBy}";

        return $this->db->fetchAll($sql, [$postId]);
    }

    /**
     * Conta comentários de um post
     */
    public function countByPost(int $postId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE post_id = ?";
        $result = $this->db->fetchOne($sql, [$postId]);
        return $result['total'] ?? 0;
    }

    /**
     * Incrementa contador de likes
     */
    public function incrementLikes(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET likes_count = likes_count + 1 WHERE id = ?";
        $this->db->query($sql, [$id]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Decrementa contador de likes
     */
    public function decrementLikes(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?";
        $this->db->query($sql, [$id]);
        return $this->db->affectedRows() > 0;
    }
}
