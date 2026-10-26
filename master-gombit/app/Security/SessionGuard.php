<?php

declare(strict_types=1);

namespace MG\Security;

use MG\Core\Config;

/**
 * SessionGuard - oturum yaşam döngüsü güvenliği.
 * Uygulanan kontroller:
 *  - use_strict_mode + only_cookies (session fixation & cookie enjeksiyonu önleme)
 *  - HttpOnly + SameSite cookie
 *  - Login sonrası regenerate() (fixation koruması - AuthController çağırır)
 *  - Boşta kalma süresi (idle timeout) ve mutlak süre
 *  - IDLE_TIMEOUT / ABSOLUTE lifetime aşımında tam temizlik
 */
final class SessionGuard
{
    private const KEY_CREATED = '_mg_created';
    private const KEY_LAST    = '_mg_last';
    private const KEY_UID     = 'user_id';
    private const KEY_ROLE    = 'role_snapshot';

    public function __construct(private readonly object $container) {}

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $name = preg_replace('/[^A-Za-z0-9_]/', '', (string)Config::get('env.session_name', 'MGBSID')) ?: 'MGBSID';
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,                                   // session cookie
            'path' => '/',
            'domain' => '',
            'secure' => filter_var((string)Config::get('env.cookie_secure', 'false'), FILTER_VALIDATE_BOOLEAN),
            'httponly' => true,
            'samesite' => in_array((string)Config::get('env.cookie_samesite', 'Lax'), ['Lax','Strict','None'], true)
                        ? (string)Config::get('env.cookie_samesite', 'Lax') : 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        @session_start();

        if (!isset($_SESSION[self::KEY_CREATED])) {
            $this->regenerate();          // yeni sabitlenmiş SID'i at (strict mode da reddeder)
        }
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);      // eski oturum dosyasını sil -> fixation önleme
        $_SESSION[self::KEY_CREATED] = time();
        $_SESSION[self::KEY_LAST] = time();
    }

    public function expireIfNeeded(): void
    {
        $idle = (int)Config::get('env.session_lifetime', 1800);
        $now = time();
        $last = (int)($_SESSION[self::KEY_LAST] ?? $now);
        $created = (int)($_SESSION[self::KEY_CREATED] ?? $now);
        // idle > 30dk veya mutlak > 8sa => destroy
        if (($now - $last) > $idle || ($now - $created) > 8 * 3600) {
            $this->destroy();
        }
        $_SESSION[self::KEY_LAST] = $now;
    }

    /** @param array<string,mixed> $snap */
    public function loginUser(int $userId, string $roleName): void
    {
        $this->regenerate();               // login anında SID yenileme (zorunlu!)
        $_SESSION[self::KEY_UID] = $userId;
        $_SESSION[self::KEY_ROLE] = $roleName;
    }

    public function logout(): void
    {
        $this->destroy();
    }

    private function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        @session_destroy();
    }

    public function userId(): ?int
    {
        $id = $_SESSION[self::KEY_UID] ?? null;
        return is_int($id) ? $id : null;
    }

    /** Geçerli kullanıcıyı DB'den tazeler (yetki snapshot'ı bayatlamasın). */
    public function currentUser(): ?\MG\Models\User
    {
        $id = $this->userId();
        if ($id === null) {
            return null;
        }
        /** @var \PDO $pdo */
        $pdo = $this->container->get(\PDO::class);
        return \MG\Models\User::findById($pdo, $id);
    }
}
