<?php

namespace Werk365\JwtAuthRoles\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthException extends HttpException
{
    public static function auth(int $status, string $message): self
    {
        $exception = new static($status, $message, null, []);

        return $exception;
    }
}
