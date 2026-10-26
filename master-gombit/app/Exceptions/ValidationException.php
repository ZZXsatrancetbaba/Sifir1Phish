<?php

declare(strict_types=1);

namespace MG\Exceptions;

/** Girdi doğrulama hataları -> 422. Mesajlar alan bazlı ve güvenlidir. */
final class ValidationException extends AppException
{
    /** @param array<string,string> $errors field => message */
    public function __construct(private readonly array $errors = [], string $message = 'Girdi doğrulaması başarısız.')
    {
        parent::__construct($message, 422);
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
