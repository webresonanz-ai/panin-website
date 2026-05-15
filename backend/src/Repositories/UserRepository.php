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
            'SELECT id, name, email, role, password_hash, created_at, updated_at FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);

        return $statement->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $email, string $role, string $passwordHash): array
    {
        $statement = $this->database->connection()->prepare(
            'INSERT INTO users (name, email, role, password_hash) VALUES (:name, :email, :role, :password_hash)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password_hash' => $passwordHash,
        ]);

        return $this->findById((int) $this->database->connection()->lastInsertId());
    }
}
