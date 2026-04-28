<?php

namespace App\Core;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly array $errors = []
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
