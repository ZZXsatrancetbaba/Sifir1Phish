<?php

declare(strict_types=1);

namespace MG\Security;

/**
 * PasswordHasher - Argon2id tercihli parola hash'leme.
 *  - PHP 8.3 SODIUM_PWD_HASH_ARGON2ID veya PASSWORD_ARGON2ID kullanır.
 *  - needsRehash() ile eski hash'ler (bcrypt seed) ilk login'de yükseltilir.
 *  - Sabit-zamanlı verify (password_verify).
 */
final class PasswordHasher
{
    /** @param array<string,int> $options */
    public static function hash(string $plain): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($plain, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64 MB
                'time_cost'   => 4,
                'threads'     => 3,
            ]);
        }
        // Fallback: bcrypt cost 12 (argon2 derlenmemiş sistemler için eğitim notu)
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return defined('PASSWORD_ARGON2ID') && password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3,
        ]);
    }

    /** Güç kontrolü: uzunluk + karmaşıklık (kaba tahmin sözlüğüne karşı basit heuristic). */
    public static function isStrongEnough(string $plain): bool
    {
        if (strlen($plain) < 10 || strlen($plain) > 190) { // bcrypt 72 byte limiti & DoS koruması
            return false;
        }
        $classes = (int)preg_match('/[a-z]/', $plain)
                 + (int)preg_match('/[A-Z]/', $plain)
                 + (int)preg_match('/\d/', $plain)
                 + (int)preg_match('/[^A-Za-z0-9]/', $plain);
        return $classes >= 3;
    }
}
