<?php

namespace App\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function handle(Request $request, callable $next): mixed
    {
        header('Access-Control-Allow-Origin: ' . $this->config->get('app.frontend_url'));
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Credentials: true');

        if ($request->method() === 'OPTIONS') {
            Response::noContent();
            return null;
        }

        return $next($request);
    }
}
