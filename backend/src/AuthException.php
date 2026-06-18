<?php

declare(strict_types=1);

namespace App;

class AuthException extends \RuntimeException
{
    public function __construct(
        string $message = 'Unauthorized.',
        private readonly int $statusCode = 401,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

