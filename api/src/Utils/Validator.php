<?php

namespace Api\Utils;

class Validator
{
    private $errors = [];

    /**
     * Valida email
     */
    public function email(string $value, string $field = 'email'): self
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Email inválido';
        }
        return $this;
    }

    /**
     * Valida se não está vazio
     */
    public function required(string $value, string $field): self
    {
        if (empty(trim($value))) {
            $this->errors[$field] = ucfirst($field) . ' é obrigatório';
        }
        return $this;
    }

    /**
     * Valida comprimento mínimo
     */
    public function minLength(string $value, int $min, string $field): self
    {
        if (strlen($value) < $min) {
            $this->errors[$field] = ucfirst($field) . ' deve ter no mínimo ' . $min . ' caracteres';
        }
        return $this;
    }

    /**
     * Valida comprimento máximo
     */
    public function maxLength(string $value, int $max, string $field): self
    {
        if (strlen($value) > $max) {
            $this->errors[$field] = ucfirst($field) . ' deve ter no máximo ' . $max . ' caracteres';
        }
        return $this;
    }

    /**
     * Valida se é número
     */
    public function numeric(string $value, string $field): self
    {
        if (!is_numeric($value)) {
            $this->errors[$field] = ucfirst($field) . ' deve ser um número';
        }
        return $this;
    }

    /**
     * Valida padrão (regex)
     */
    public function pattern(string $value, string $pattern, string $field): self
    {
        if (!preg_match($pattern, $value)) {
            $this->errors[$field] = ucfirst($field) . ' está em formato inválido';
        }
        return $this;
    }

    /**
     * Valida username (letras, números, underscore)
     */
    public function username(string $value, string $field = 'username'): self
    {
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $value)) {
            $this->errors[$field] = 'Username deve ter 3-20 caracteres (letras, números, underscore)';
        }
        return $this;
    }

    /**
     * Retorna true se há erros
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Retorna o array de erros
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna o primeiro erro
     */
    public function getFirstError(): string
    {
        return array_values($this->errors)[0] ?? '';
    }

    /**
     * Retorna o campo do primeiro erro
     */
    public function getFirstErrorField(): string
    {
        return array_keys($this->errors)[0] ?? '';
    }
}
