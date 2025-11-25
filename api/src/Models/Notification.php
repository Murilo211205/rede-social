<?php

namespace Api\Models;

class Notification extends Model
{
    protected $table = 'notifications';

    // ========== IMPLEMENTAÇÃO DOS MÉTODOS ABSTRATOS ==========

    /**
     * INSERT - Insere nova notificação
     * Implementação específica do Notification
     */
    public function insert(array $data): int|null
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, from_user_id, type, post_id, comment_id) 
                VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['user_id'] ?? 0,
            $data['from_user_id'] ?? 0,
            $data['type'] ?? '',
            $data['post_id'] ?? null,
            $data['comment_id'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * UPDATE - Atualiza notificação
     * Implementação específica do Notification
     */
    public function update(int $id, array $data): bool
    {
        // Notificações geralmente só atualizam is_read via markAsRead
        if (isset($data['is_read'])) {
            return $this->markAsRead($id);
        }
        return false;
    }

    // ========== MÉTODOS ESPECÍFICOS DO NOTIFICATION ==========

    /**
     * Lista notificações do usuário
     */
    public function getByUser(int $userId, int $limit = 20): array
    {
        $sql = "SELECT n.*, u.username, u.avatar_url FROM {$this->table} n
                LEFT JOIN users u ON n.from_user_id = u.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * Conta notificações não lidas
     */
    public function countUnread(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE user_id = ? AND is_read = FALSE";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }

    /**
     * Marca como lida
     */
    public function markAsRead(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET is_read = TRUE WHERE id = ?";
        $this->db->query($sql, [$id]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Marca todas como lidas
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE {$this->table} SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
        $this->db->query($sql, [$userId]);
        return $this->db->affectedRows() > 0;
    }

    /**
     * Verifica se notificação de follow já existe
     */
    public function followNotificationExists(int $userId, int $fromUserId): bool
    {
        $sql = "SELECT id FROM {$this->table} 
                WHERE user_id = ? AND from_user_id = ? AND type = 'follow' AND is_read = FALSE";
        return $this->db->fetchOne($sql, [$userId, $fromUserId]) !== null;
    }
}
