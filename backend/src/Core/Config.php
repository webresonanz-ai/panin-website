<?php

namespace App\Core;

class Config
{
    private array $items = [];

    public function __construct(string $configPath)
    {
        foreach (glob($configPath . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $this->items[basename($file, '.php')] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
