<?php

namespace Api\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private const ALGORITHM = 'HS256';
    private const EXPIRY = 86400; // 24 horas

    /**
     * Obtém a chave secreta do arquivo config.ini
     */
    private static function getSecret(): string
    {
        static $secret = null;
        
        if ($secret === null) {
            $configPath = __DIR__ . '/../../config.ini';
            
            if (!file_exists($configPath)) {
                throw new \Exception('Arquivo config.ini não encontrado');
            }

            $config = parse_ini_file($configPath, true);
            
            if (!$config || !isset($config['jwt']['secret'])) {
                throw new \Exception('Chave JWT não configurada no config.ini');
            }

            $secret = $config['jwt']['secret'];
        }
        
        return $secret;
    }

    /**
     * Gera um JWT token
     */
    public static function generate(array $data): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + self::EXPIRY,
            'data' => $data
        ];

        return JWT::encode($payload, self::getSecret(), self::ALGORITHM);
    }

    /**
     * Valida e decodifica um JWT token
     */
    public static function verify(string $token): array|null
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecret(), self::ALGORITHM));
            return (array) $decoded->data;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extrai dados do token sem validar (cuidado!)
     */
    public static function decode(string $token): array|null
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode($parts[1]), true);
            return $payload['data'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
