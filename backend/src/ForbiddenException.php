<?php

declare(strict_types=1);

namespace App;

class ForbiddenException extends AuthException
{
    public function __construct(string $message = 'Forbidden.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $code, $previous);
    }
}

