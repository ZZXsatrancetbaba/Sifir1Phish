<?php

declare(strict_types=1);

namespace MG\Security;

use MG\Core\Config;

/**
 * SecurityHeaders - uygulama katmanında defense-in-depth header'ları.
 * Nginx de aynı header'ları set eder; ikili kontrol örnek olsun diye bilinçli.
 * HSTS yalnızca HTTPS'te gönderilir (HTTP'de tarayıcı önbelleği kirlenmesin).
 */
final class SecurityHeaders
{
    public function apply(): void
    {
        $csp = "default-src 'self'; "
             . "script-src 'self'; "
             . "style-src 'self'; "
             . "img-src 'self' data:; "
             . "frame-ancestors 'none'; "
             . "base-uri 'self'; "
             . "form-action 'self'; "
             . "object-src 'none'";

        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        $https = (($_SERVER['HTTPS'] ?? '') === 'on')
              || ((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Duyarlı sayfalarda cache kırma (login/dashboard/admin)
        $path = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        if (preg_match('#^/(login|logout|dashboard|admin|labs)#', $path) === 1) {
            header('Cache-Control: no-store, max-age=0');
            header('Pragma: no-cache');
        }

        header_remove('X-Powered-By');
        unset($path, $csp);
    }
}
