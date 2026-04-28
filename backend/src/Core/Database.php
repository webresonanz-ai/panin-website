<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    public function __construct(private readonly Config $config)
    {
        $this->connection = $this->connect();
    }

    public function connection(): PDO
    {
        return $this->connection;
    }

    private function connect(): PDO
    {
        $host = $this->config->get('database.host');
        $port = $this->config->get('database.port');
        $database = $this->config->get('database.database');
        $charset = $this->config->get('database.charset', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

        try {
            return new PDO(
                $dsn,
                $this->config->get('database.username'),
                $this->config->get('database.password'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new ApiException('Database connection failed. Check backend/.env settings.', 500);
        }
    }
}
