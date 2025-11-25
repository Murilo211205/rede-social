<?php

namespace Api\Utils;

class StringUtils
{
    /**
     * Converte string em slug
     * "Python é Legal!" -> "python-e-legal"
     */
    public static function slugify(string $text): string
    {
        // Converte para lowercase
        $text = strtolower($text);

        // Remove acentos
        $text = preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($text, ENT_QUOTES, 'UTF-8'));
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Remove caracteres especiais, mantém só letras, números e hífen
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');

        return $text;
    }

    /**
     * Sanitiza string para evitar XSS
     */
    public static function sanitize(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Remove tags HTML
     */
    public static function stripTags(string $text, string $allowed = ''): string
    {
        return strip_tags($text, $allowed);
    }

    /**
     * Trunca string com reticências
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }

    /**
     * Valida se é JSON válido
     */
    public static function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
