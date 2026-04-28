<?php

namespace App\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\ApiException;
use App\Core\Request;
use App\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function handle(Request $request, callable $next): mixed
    {
        $user = $this->auth->userFromToken($request->bearerToken());

        if (!$user) {
            throw new ApiException('Authentication required.', 401);
        }

        $request->setAttribute('authUser', $user);

        return $next($request);
    }
}
