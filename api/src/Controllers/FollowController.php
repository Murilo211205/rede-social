<?php

namespace Api\Controllers;

use Api\Models\Follow;
use Api\Models\User;
use Api\Models\Notification;
use Api\Utils\JwtHelper;

class FollowController extends Controller
{
    private $followModel;
    private $userModel;
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->followModel = new Follow();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }

    /**
     * POST /api/users/{userId}/follow
     * Segue um usuário
     */
    public function follow(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $userId = (int)($this->getParam('user_id') ?? 0);
        $user = $this->userModel->get($userId);

        if (!$user) {
            $this->notFound('Usuário');
        }

        if ($userId === $decoded['id']) {
            $this->error('Você não pode seguir a si mesmo', 'CANNOT_FOLLOW_SELF', 400);
        }

        // O banco garante unicidade via UNIQUE KEY (follower_id, following_id)
        // Se já segue, o banco lançará exception
        try {
            $this->followModel->insert([
                'follower_id' => $decoded['id'],
                'following_id' => $userId
            ]);

            // Notifica usuário
            if (!$this->notificationModel->followNotificationExists($userId, $decoded['id'])) {
                $this->notificationModel->insert([
                    'user_id' => $userId,
                    'from_user_id' => $decoded['id'],
                    'type' => 'follow',
                    'post_id' => null,
                    'comment_id' => null
                ]);
            }

            $this->success(null, 'Usuário seguido com sucesso', 201);
        } catch (\Exception $e) {
            // Captura erro de UNIQUE constraint
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && 
                strpos($e->getMessage(), 'unique_follow') !== false) {
                $this->error('Você já está seguindo este usuário', 'ALREADY_FOLLOWING', 400);
            }
            // Outros erros de banco
            $this->error('Erro ao seguir usuário', 'DATABASE_ERROR', 500);
        }
    }

    /**
     * DELETE /api/users/{userId}/follow
     * Para de seguir um usuário
     */
    public function unfollow(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $userId = (int)($this->getParam('user_id') ?? 0);
        $user = $this->userModel->get($userId);

        if (!$user) {
            $this->notFound('Usuário');
        }

        if (!$this->followModel->isFollowing($decoded['id'], $userId)) {
            $this->error('Você não está seguindo este usuário', 'NOT_FOLLOWING', 400);
        }

        try {
            $this->followModel->unfollow($decoded['id'], $userId);
        } catch (\Exception $e) {
            $this->error('Erro ao deixar de seguir', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Deixou de seguir com sucesso');
    }

    /**
     * GET /api/users/{userId}/followers
     * Lista seguidores
     */
    public function followers(): void
    {
        $userId = (int)($this->getParam('user_id') ?? 0);
        $user = $this->userModel->get($userId);

        if (!$user) {
            $this->notFound('Usuário');
        }

        $followers = $this->followModel->getFollowers($userId, 20);

        $this->success(['followers' => $followers]);
    }

    /**
     * GET /api/users/{userId}/following
     * Lista seguindo
     */
    public function following(): void
    {
        $userId = (int)($this->getParam('user_id') ?? 0);
        $user = $this->userModel->get($userId);

        if (!$user) {
            $this->notFound('Usuário');
        }

        $following = $this->followModel->getFollowing($userId, 20);

        $this->success(['following' => $following]);
    }

    /**
     * GET /api/users/{userId}/is-following
     * Verifica se está seguindo
     */
    public function isFollowing(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->success(['is_following' => false]);
            return;
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->success(['is_following' => false]);
            return;
        }

        $userId = (int)($this->getParam('user_id') ?? 0);

        $isFollowing = $this->followModel->isFollowing($decoded['id'], $userId);

        $this->success(['is_following' => $isFollowing]);
    }
}
