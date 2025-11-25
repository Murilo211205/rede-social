<?php

namespace Api\Controllers;

use Api\Models\Notification;
use Api\Utils\JwtHelper;

class NotificationController extends Controller
{
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationModel = new Notification();
    }

    /**
     * GET /api/notifications
     * Lista notificações do usuário
     */
    public function list(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $notifications = $this->notificationModel->getByUser($decoded['id'], 20);

        $this->success(['notifications' => $notifications]);
    }

    /**
     * GET /api/notifications/unread
     * Conta notificações não lidas
     */
    public function unreadCount(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->success(['unread' => 0]);
            return;
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->success(['unread' => 0]);
            return;
        }

        $unread = $this->notificationModel->countUnread($decoded['id']);

        $this->success(['unread' => $unread]);
    }

    /**
     * PUT /api/notifications/{id}/read
     * Marca notificação como lida
     */
    public function markAsRead(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $id = (int)($this->getParam('id') ?? 0);
        $notification = $this->notificationModel->get($id);

        if (!$notification) {
            $this->error('Notificação não encontrada', 'NOT_FOUND', 404);
        }

        if ($notification['user_id'] != $decoded['id']) {
            $this->error('Acesso negado', 'FORBIDDEN', 403);
        }

        try {
            $this->notificationModel->markAsRead($id);
        } catch (\Exception $e) {
            $this->error('Erro ao marcar notificação como lida', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Notificação marcada como lida');
    }

    /**
     * PUT /api/notifications/read-all
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        try {
            $this->notificationModel->markAllAsRead($decoded['id']);
        } catch (\Exception $e) {
            $this->error('Erro ao marcar notificações como lidas', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Todas as notificações foram marcadas como lidas');
    }
}
