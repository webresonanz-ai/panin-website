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

    public function register(Request $request): array
    {
        $name = trim((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $passwordConfirmation = (string) $request->input('passwordConfirmation');

        if ($name === '' || $email === '' || $password === '') {
            throw new ApiException('Name, email, and password are required.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException('Please enter a valid email address.', 422);
        }

        if (strlen($password) < 8) {
            throw new ApiException('Password must be at least 8 characters.', 422);
        }

        if ($password !== $passwordConfirmation) {
            throw new ApiException('Password confirmation does not match.', 422);
        }

        return [
            'message' => 'Registration successful.',
            'data' => $this->auth->register($name, $email, $password),
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
                    'role' => $user['role'],
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
