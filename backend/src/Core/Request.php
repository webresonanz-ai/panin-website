<?php

namespace App\Core;

class Request
{
    private array $attributes = [];
    private ?array $jsonBody = null;

    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $headers = [],
        private readonly string $rawBody = ''
    ) {
    }

    public static function capture(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            rtrim($uri, '/') ?: '/',
            $_GET,
            self::captureHeaders(),
            file_get_contents('php://input') ?: ''
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        return $headers[strtolower($key)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = trim((string) $this->header('Authorization', ''));

        if ($header === '' || stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        return trim(substr($header, 7));
    }

    public function body(): array
    {
        if ($this->isMultipart()) {
            return $_POST;
        }

        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        if ($this->rawBody === '') {
            return $this->jsonBody = [];
        }

        $decoded = json_decode($this->rawBody, true);

        if (!is_array($decoded)) {
            throw new ApiException('Invalid JSON payload.', 422);
        }

        return $this->jsonBody = $decoded;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body()[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function files(): array
    {
        return $_FILES;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    private function isMultipart(): bool
    {
        $contentType = (string) $this->header('Content-Type', '');

        return str_starts_with(strtolower($contentType), 'multipart/form-data');
    }

    private static function captureHeaders(): array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
                continue;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headerName = str_replace('_', '-', $key);
                $headers[$headerName] = $value;
            }
        }

        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'Authorization'] as $key) {
            if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
                $headers['Authorization'] = $_SERVER[$key];
                break;
            }
        }

        return $headers;
    }
}
