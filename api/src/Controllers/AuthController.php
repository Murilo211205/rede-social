<?php

namespace Api\Controllers;

use Api\Models\User;
use Api\Utils\JwtHelper;
use Api\Utils\Validator;

class AuthController extends Controller
{
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        $data = $this->getJsonBody();

        // Validação
        $validator = new Validator();
        $validator
            ->required($data['username'] ?? '', 'username')
            ->username($data['username'] ?? '', 'username')
            ->required($data['email'] ?? '', 'email')
            ->email($data['email'] ?? '', 'email')
            ->required($data['password'] ?? '', 'password')
            ->minLength($data['password'] ?? '', 8, 'password');

        if ($validator->hasErrors()) {
            $this->error(
                $validator->getFirstError(),
                'VALIDATION_ERROR',
                400,
                $validator->getFirstErrorField()
            );
        }

        // Cria usuário
        // O banco garante unicidade de email/username via UNIQUE constraint
        // Se houver duplicata, o banco lançará uma exception que tratamos abaixo
        try {
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $userId = $this->userModel->insert([
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => $passwordHash,
                'bio' => $data['bio'] ?? null
            ]);

            if (!$userId) {
                $this->error('Erro ao criar usuário', 'CREATION_ERROR', 500);
            }

            $user = $this->userModel->get($userId);
            // Mantém email pois usuário acabou de se registrar
            $user = $this->sanitizeOwnUser($user);

            $token = JwtHelper::generate(['id' => $userId, 'username' => $data['username']]);

            $this->success(
                ['user' => $user, 'token' => $token],
                'Usuário registrado com sucesso',
                201
            );
        } catch (\Exception $e) {
            // Captura erro de UNIQUE constraint do MySQL
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                // Identifica qual campo está duplicado
                if (strpos($e->getMessage(), 'users.email') !== false || strpos($e->getMessage(), "for key 'email'") !== false) {
                    $this->error('Email já cadastrado', 'EMAIL_EXISTS', 400, 'email');
                } else if (strpos($e->getMessage(), 'users.username') !== false || strpos($e->getMessage(), "for key 'username'") !== false) {
                    $this->error('Username já cadastrado', 'USERNAME_EXISTS', 400, 'username');
                }
                // Caso genérico de duplicata
                $this->error('Dados já cadastrados', 'DUPLICATE_ENTRY', 400);
            }
            
            // Outros erros de banco
            $this->error('Erro ao criar usuário', 'DATABASE_ERROR', 500);
        }
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        $data = $this->getJsonBody();

        // Validação
        $validator = new Validator();
        $validator
            ->required($data['email'] ?? '', 'email')
            ->email($data['email'] ?? '', 'email')
            ->required($data['password'] ?? '', 'password');

        if ($validator->hasErrors()) {
            $this->error($validator->getFirstError(), 'VALIDATION_ERROR', 400);
        }

        // Busca usuário
        $user = $this->userModel->findByEmail($data['email']);
        if (!$user) {
            $this->error('Email ou senha incorretos', 'AUTH_FAILED', 401);
        }

        // Verifica se está banido
        if ($user['is_banned']) {
            $this->error('Sua conta foi banida', 'ACCOUNT_BANNED', 403);
        }

        // Verifica senha
        if (!password_verify($data['password'], $user['password_hash'])) {
            $this->error('Email ou senha incorretos', 'AUTH_FAILED', 401);
        }

        // Mantém email pois é o próprio usuário fazendo login
        $user = $this->sanitizeOwnUser($user);

        $token = JwtHelper::generate(['id' => $user['id'], 'username' => $user['username']]);

        $this->success(
            ['user' => $user, 'token' => $token],
            'Login realizado com sucesso'
        );
    }

    /**
     * POST /api/auth/verify
     * Verifica se token é válido
     */
    public function verify(): void
    {
        $token = $this->getAuthToken();

        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);

        if (!$decoded) {
            $this->unauthorized();
        }

        $user = $this->userModel->get($decoded['id']);

        if (!$user || $user['is_banned']) {
            $this->unauthorized();
        }

        // Mantém email pois é o próprio usuário verificando seu token
        $user = $this->sanitizeOwnUser($user);

        $this->success(['user' => $user], 'Token válido');
    }
}
