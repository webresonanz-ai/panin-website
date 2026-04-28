<?php

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Services\AuthService;

class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(Request $request): array
    {
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');

        if ($email === '' || $password === '') {
            throw new ApiException('Email and password are required.', 422);
        }

        return [
            'message' => 'Login successful.',
            'data' => $this->auth->attempt($email, $password),
        ];
    }

    public function me(Request $request): array
    {
        $user = $request->attribute('authUser');

        return [
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'expiresAt' => $user['token_expires_at'],
                ],
            ],
        ];
    }

    public function logout(Request $request): array
    {
        $this->auth->revokeToken($request->bearerToken());

        return [
            'message' => 'Logout successful.',
        ];
    }
}
