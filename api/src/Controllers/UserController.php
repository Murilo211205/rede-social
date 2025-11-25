<?php

namespace Api\Controllers;

use Api\Models\User;
use Api\Models\Post;
use Api\Utils\JwtHelper;
use Api\Utils\Validator;

class UserController extends Controller
{
    private $userModel;
    private $postModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->postModel = new Post();
    }

    /**
     * GET /api/users/{username}
     * Obtém perfil de usuário
     */
    public function profile(): void
    {
        $username = $this->getParam('username') ?? '';

        if (empty($username)) {
            $this->error('Username é obrigatório', 'MISSING_PARAM', 400);
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || $user['is_banned']) {
            $this->notFound('Usuário');
        }

        // Remove dados sensíveis para visualização pública
        $user = $this->sanitizeUser($user);

        $followers = $this->userModel->countFollowers($user['id']);
        $following = $this->userModel->countFollowing($user['id']);
        
        // Conta posts do usuário
        $postsCount = $this->postModel->countByUser($user['id']);

        $user['followers'] = $followers;
        $user['following'] = $following;
        $user['posts_count'] = $postsCount;

        $this->success($user);
    }

    /**
     * GET /api/users/{username}/posts
     * Lista posts de um usuário
     */
    public function posts(): void
    {
        $username = $this->getParam('username') ?? '';
        $page = (int)($this->getParam('page') ?? 1);

        $user = $this->userModel->findByUsername($username);

        if (!$user || $user['is_banned']) {
            $this->notFound('Usuário');
        }

        $posts = $this->postModel->postsByUser($user['id'], $page, 10);

        $this->success(['posts' => $posts]);
    }

    /**
     * PUT /api/users/profile
     * Atualiza perfil do usuário
     */
    public function updateProfile(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $data = $this->getJsonBody();

        $validator = new Validator();
        $validator->maxLength($data['bio'] ?? '', 500, 'bio');

        if ($validator->hasErrors()) {
            $this->error($validator->getFirstError(), 'VALIDATION_ERROR', 400);
        }

        // Buscar dados atuais para mesclar
        $currentUser = $this->userModel->get($decoded['id']);

        // O banco garante unicidade de email/username via UNIQUE constraint
        try {
            $this->userModel->update($decoded['id'], [
                'username' => $data['username'] ?? $currentUser['username'],
                'email' => $data['email'] ?? $currentUser['email'],
                'bio' => $data['bio'] ?? $currentUser['bio'],
                'avatar_url' => $data['avatar_url'] ?? $currentUser['avatar_url']
            ]);
        } catch (\Exception $e) {
            // Captura erro de UNIQUE constraint do MySQL
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'users.email') !== false || strpos($e->getMessage(), "for key 'email'") !== false) {
                    $this->error('Email já cadastrado', 'EMAIL_EXISTS', 400, 'email');
                } else if (strpos($e->getMessage(), 'users.username') !== false || strpos($e->getMessage(), "for key 'username'") !== false) {
                    $this->error('Username já cadastrado', 'USERNAME_EXISTS', 400, 'username');
                }
                $this->error('Dados já cadastrados', 'DUPLICATE_ENTRY', 400);
            }
            // Outros erros de banco
            $this->error('Erro ao atualizar perfil', 'DATABASE_ERROR', 500);
        }

        $user = $this->userModel->get($decoded['id']);
        // Mantém email pois é o próprio usuário
        $user = $this->sanitizeOwnUser($user);

        $this->success($user, 'Perfil atualizado com sucesso');
    }

    /**
     * GET /api/users/search?q=query
     */
    public function search(): void
    {
        $query = $this->getParam('q') ?? '';

        if (strlen($query) < 2) {
            $this->error('Query deve ter no mínimo 2 caracteres', 'INVALID_QUERY', 400);
        }

        $users = $this->userModel->search($query, 10);

        $this->success(['users' => $users]);
    }

    /**
     * DELETE /api/users/{id}
     * Deleta conta do usuário (admin)
     */
    public function deleteUser(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $adminUser = $this->userModel->get($decoded['id']);
        if (!$adminUser['is_admin']) {
            $this->forbidden();
        }

        $userId = (int)($this->getParam('id') ?? 0);
        $user = $this->userModel->get($userId);

        if (!$user) {
            $this->notFound('Usuário');
        }

        try {
            $this->userModel->delete($userId);
        } catch (\Exception $e) {
            $this->error('Erro ao deletar usuário', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Usuário deletado com sucesso');
    }

    /**
     * POST /api/users/{id}/ban
     * Bane um usuário (admin)
     */
    public function ban(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $adminUser = $this->userModel->get($decoded['id']);
        if (!$adminUser['is_admin']) {
            $this->forbidden();
        }

        $userId = (int)($this->getParam('id') ?? 0);
        $user = $this->userModel->get($userId);

        if (!$user) {
            $this->notFound('Usuário');
        }

        try {
            $this->userModel->ban($userId);
        } catch (\Exception $e) {
            $this->error('Erro ao banir usuário', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Usuário banido com sucesso');
    }
}
