<?php

declare(strict_types=1);

namespace MG\Security;

use MG\Core\Request;
use MG\Exceptions\AppException;

/**
 * CsrfTokenManager - Synchronizer Token Pattern.
 *  - Token oturumda tutulur; form alanı (csrf_token) VEYA X-CSRF-Token header'ı.
 *  - hash_equals ile sabit zamanlı karşılaştırma (timing attack önleme).
 *  - Rotasyon: login/logout gibi kritik anlarda regenerate() çağrılır.
 * NOT: Token secret DEĞİLDİR (hassas veri değildir), ama tahmin edilemez olmalı.
 */
final class CsrfTokenManager
{
    private const SESSION_KEY = '_mg_csrf';
    public const FIELD = 'csrf_token';
    public const HEADER = 'x-csrf-token';

    public function __construct(private readonly SessionGuard $session) {}

    public function token(): string
    {
        $tok = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($tok) || strlen($tok) < 32) {
            $tok = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $tok;
        }
        return $tok;
    }

    public function rotate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }

    public function validateOrFail(Request $req): void
    {
        $sent = $req->str(self::FIELD, '') !== '' ? $req->str(self::FIELD) : $req->header(self::HEADER);
        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        if (!is_string($stored) || $stored === '' || !hash_equals($stored, $sent)) {
            throw new AppException('Oturum doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.', 419);
        }
    }
}
