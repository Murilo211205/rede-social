<?php

namespace Api\Utils;

class Database
{
    private $conn;
    private static $instance = null;

    private function __construct()
    {
        try {
            // Carrega configurações do arquivo .ini
            $configPath = __DIR__ . '/../../config.ini';
            
            if (!file_exists($configPath)) {
                throw new \Exception('Arquivo config.ini não encontrado. Copie config.ini.example para config.ini');
            }

            $config = parse_ini_file($configPath, true);
            
            if (!$config || !isset($config['database'])) {
                throw new \Exception('Erro ao carregar configurações do banco de dados');
            }

            $db = $config['database'];
            
            $this->conn = new \mysqli(
                $db['host'],
                $db['user'],
                $db['password'],
                $db['database']
            );
            
            $this->conn->set_charset('utf8mb4');

            if ($this->conn->connect_error) {
                throw new \Exception('Erro ao conectar: ' . $this->conn->connect_error);
            }
        } catch (\Exception $e) {
            die(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * Singleton: Retorna a mesma instância de conexão
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna a conexão MySQLi
     */
    public function getConnection(): \mysqli
    {
        return $this->conn;
    }

    /**
     * Executa query e retorna resultado
     */
    public function query(string $sql, array $params = []): \mysqli_result|bool
    {
        if (empty($params)) {
            return $this->conn->query($sql);
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception('Erro ao preparar query: ' . $this->conn->error);
        }

        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                $types .= match (true) {
                    is_int($param) => 'i',
                    is_double($param) => 'd',
                    default => 's',
                };
            }
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new \Exception('Erro ao executar query: ' . $stmt->error);
        }

        return $stmt->get_result();
    }

    /**
     * Retorna um único resultado
     */
    public function fetchOne(string $sql, array $params = []): array|null
    {
        $result = $this->query($sql, $params);
        return $result->fetch_assoc();
    }

    /**
     * Retorna todos os resultados
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $result = $this->query($sql, $params);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Retorna o ID da última inserção
     */
    public function lastInsertId(): int
    {
        return $this->conn->insert_id;
    }

    /**
     * Retorna o número de linhas afetadas
     */
    public function affectedRows(): int
    {
        return $this->conn->affected_rows;
    }

    /**
     * Escapa string para evitar SQL Injection
     */
    public function escape(string $str): string
    {
        return $this->conn->real_escape_string($str);
    }

    /**
     * Fecha a conexão
     */
    public function close(): void
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
