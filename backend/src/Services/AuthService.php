<?php

namespace App\Services;

use App\Core\ApiException;
use App\Core\Config;
use App\Core\Database;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        private readonly Database $database,
        private readonly UserRepository $users,
        private readonly Config $config
    ) {
    }

    public function attempt(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new ApiException('Invalid email or password.', 401);
        }

        return $this->issueTokenForUser($user);
    }

    public function register(string $name, string $email, string $password): array
    {
        if ($this->users->findByEmail($email)) {
            throw new ApiException('An account with this email already exists.', 422);
        }

        $user = $this->users->create(
            $name,
            $email,
            'user',
            password_hash($password, PASSWORD_DEFAULT)
        );

        return $this->issueTokenForUser($user);
    }

    private function issueTokenForUser(array $user): array
    {
        if (isset($user['password_hash'])) {
            unset($user['password_hash']);
        }

        $plainTextToken = bin2hex(random_bytes(32));
        $expiresAt = (new \DateTimeImmutable())
            ->modify('+' . $this->config->get('app.auth_token_ttl_hours', 12) . ' hours')
            ->format('Y-m-d H:i:s');

        $statement = $this->database->connection()->prepare(
            'INSERT INTO api_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NOW())'
        );
        $statement->execute([
            'user_id' => $user['id'],
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $plainTextToken,
            'expiresAt' => $expiresAt,
            'user' => $user,
        ];
    }

    public function userFromToken(?string $plainTextToken): ?array
    {
        if (!$plainTextToken) {
            return null;
        }

        $statement = $this->database->connection()->prepare(
            'SELECT users.id, users.name, users.email, users.role, users.created_at, users.updated_at, api_tokens.id AS token_id, api_tokens.expires_at
             FROM api_tokens
             INNER JOIN users ON users.id = api_tokens.user_id
             WHERE api_tokens.token_hash = :token_hash
               AND api_tokens.expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute([
            'token_hash' => hash('sha256', $plainTextToken),
        ]);

        $record = $statement->fetch();

        if (!$record) {
            return null;
        }

        return [
            'id' => (int) $record['id'],
            'name' => $record['name'],
            'email' => $record['email'],
            'role' => $record['role'],
            'created_at' => $record['created_at'],
            'updated_at' => $record['updated_at'],
            'token_id' => (int) $record['token_id'],
            'token_expires_at' => $record['expires_at'],
        ];
    }

    public function revokeToken(?string $plainTextToken): void
    {
        if (!$plainTextToken) {
            return;
        }

        $statement = $this->database->connection()->prepare('DELETE FROM api_tokens WHERE token_hash = :token_hash');
        $statement->execute([
            'token_hash' => hash('sha256', $plainTextToken),
        ]);
    }
}
