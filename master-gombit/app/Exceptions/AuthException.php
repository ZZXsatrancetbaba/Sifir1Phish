<?php

declare(strict_types=1);

namespace MG\Exceptions;

/** Kimlik doğrulama/yetki hataları -> 401/403. */
final class AuthException extends AppException
{
    public static function invalidCredentials(): self
    {
        // Kullanıcı-var/yok sızıntısı olmaması için JENERİK mesaj (enumeration önleme)
        return new self('Kullanıcı adı veya parola hatalı.', 401);
    }

    public static function locked(): self
    {
        return new self('Hesap geçici olarak kilitli. Bir süre sonra tekrar deneyin.', 429);
    }

    public static function forbidden(): self
    {
        return new self('Bu kaynağa erişim yetkiniz yok.', 403);
    }
}
