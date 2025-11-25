<?php

namespace Api\Models;

class Post extends Model
{
    protected $table = 'posts';

    // ========== IMPLEMENTAÇÃO DOS MÉTODOS ABSTRATOS ==========

    /**
     * INSERT - Insere novo post
     * Implementação específica do Post
     */
    public function insert(array $data): int|null
    {
        $sql = "INSERT INTO {$this->table} (user_id, title, slug, content) 
                VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['user_id'] ?? 0,
            $data['title'] ?? '',
            $data['slug'] ?? '',
            $data['content'] ?? ''
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * UPDATE - Atualiza post
     * Implementação específica do Post
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} 
                SET title = ?, content = ? 
                WHERE id = ?";
        $this->db->query($sql, [
            $data['title'] ?? '',
            $data['content'] ?? '',
            $id
        ]);
        return $this->db->affectedRows() > 0;
    }

    // ========== MÉTODOS ESPECÍFICOS DO POST ==========

    /**
     * Encontra por slug
     */
    public function findBySlug(string $slug): array|null
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Lista posts com paginação e filtro
     */
    public function listWithUser(int $page = 1, string $sort = 'recent', int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $orderBy = match ($sort) {
            'popular' => 'p.likes_count DESC',
            default => 'p.created_at DESC', // recent
        };

        $sql = "SELECT 
                    p.*,
                    u.id as user_id,
                    u.username,
                    u.avatar_url
                FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";

        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    /**
     * Conta posts
     */
    public function countPosts(): int
    {
        return $this->count();
    }

    /**
     * Conta posts de um usuário específico
     */
    public function countByUser(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }

    /**
     * Posts de um usuário
     */
    public function postsByUser(int $userId, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT 
                    p.*,
                    u.id as author_id,
                    u.username,
                    u.avatar_url,
                    u.bio
                FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.user_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
    }

    /**
     * Busca posts por título
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = '%' . $query . '%';
        $sql = "SELECT p.*, u.username, u.avatar_url FROM {$this->table} p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.title LIKE ? OR p.content LIKE ?
                ORDER BY p.created_at DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$query, $query, $limit]);
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

    /**
     * Verifica se slug existe
     */
    public function slugExists(string $slug): bool
    {
        return $this->findBySlug($slug) !== null;
    }
}
