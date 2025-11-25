<?php

namespace Api\Controllers;

abstract class Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = \Api\Utils\Database::getInstance();
    }

    /**
     * Retorna resposta JSON de sucesso
     */
    protected function success($data = null, string $message = 'Sucesso', int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Retorna resposta JSON de erro
     */
    protected function error(string $message, string $code = 'ERROR', int $httpCode = 400, string $field = null): void
    {
        http_response_code($httpCode);
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
        if ($field) {
            $response['field'] = $field;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Retorna erro 401 (Não autenticado)
     */
    protected function unauthorized(): void
    {
        $this->error('Não autenticado', 'UNAUTHORIZED', 401);
    }

    /**
     * Retorna erro 403 (Não autorizado)
     */
    protected function forbidden(): void
    {
        $this->error('Acesso negado', 'FORBIDDEN', 403);
    }

    /**
     * Retorna erro 404 (Não encontrado)
     */
    protected function notFound(string $resource = 'Recurso'): void
    {
        $this->error($resource . ' não encontrado', 'NOT_FOUND', 404);
    }

    /**
     * Obtém o body da requisição como array
     */
    protected function getJsonBody(): array
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        return $data ?? [];
    }

    /**
     * Obtém parâmetro GET
     */
    protected function getParam(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Obtém header Authorization e extrai o token
     */
    protected function getAuthToken(): string|null
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Remove dados sensíveis do usuário para exibição pública
     */
    protected function sanitizeUser(array $user): array
    {
        unset($user['password_hash']);
        unset($user['email']);          // Email é privado
        unset($user['is_admin']);
        unset($user['is_banned']);
        return $user;
    }

    /**
     * Remove dados sensíveis do usuário para o próprio usuário
     * (mantém email pois é dele mesmo)
     */
    protected function sanitizeOwnUser(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }
}
