<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function options(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('OPTIONS', $path, $handler, $middleware);
    }

    public function dispatch(Request $request, Application $app): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $pattern = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (!preg_match($pattern, $request->path(), $matches)) {
                continue;
            }

            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $request->setAttribute($key, $value);
                }
            }

            return $this->runMiddleware($route, $request, $app);
        }

        throw new ApiException('Route not found.', 404);
    }

    private function add(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function runMiddleware(array $route, Request $request, Application $app): mixed
    {
        $handler = fn (Request $request) => $this->resolveHandler($route['handler'], $request, $app);

        foreach (array_reverse($route['middleware']) as $middlewareClass) {
            $next = $handler;
            $middleware = $app->make($middlewareClass);

            $handler = fn (Request $request) => $middleware->handle($request, $next);
        }

        return $handler($request);
    }

    private function resolveHandler(callable|array $handler, Request $request, Application $app): mixed
    {
        if (is_callable($handler)) {
            return $handler($request, $app);
        }

        [$controller, $method] = $handler;
        $instance = $app->make($controller);

        return $instance->$method($request);
    }
}
