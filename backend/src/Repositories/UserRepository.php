<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class UserRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, email, password_hash, created_at, updated_at FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, email, created_at, updated_at FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }
}
