<?php

declare(strict_types=1);

namespace MG\Exceptions;

/** Uygulama hataları: HTTP status taşır; mesaj kullanıcıya gösterilebilir olmalı. */
class AppException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 400)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function forbiddenAdmin(): self
    {
        return new self('Yönetici erişimi reddedildi.', 403);
    }
}
