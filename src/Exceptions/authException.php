<?php

namespace werk365\jwtauthroles\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class authException extends HttpException
{
    public static function auth(int $status, string $message): self
    {
        $exception = new static($status, $message, null, []);
        return $exception;
    }
}
