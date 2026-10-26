<?php

declare(strict_types=1);

namespace MG\Core;

/**
 * Config - basit, read-only yapılandırma erişimi.
 * .env değerleri docker/entrypoint veya compose env olarak gelir;
 * burada getenv() ile okunur. Dosya tabanlı config/*.php merge edilir.
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $data = [];

    public static function load(string|NULL $rootPath): void
    {
        if ($rootPath === null) {
            return;
        }
        foreach (glob($rootPath . '/config/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            /** @var array<mixed> $arr */
            $arr = require $file;
            self::$data[$key] = $arr;
        }
    }

    /** Dotted erişim: Config::get('database.host') */
    public static function get(string $path, mixed $default = null): mixed
    {
        $parts = explode('.', $path);
        $node = self::$data + ['env' => self::envMap()];
        foreach ($parts as $p) {
            if (!is_array($node) || !array_key_exists($p, $node)) {
                return $default;
            }
            $node = $node[$p];
        }
        return $node;
    }

    /** @return array<string,string|false> */
    private static function envMap(): array
    {
        $keys = [
            'APP_ENV','APP_DEBUG','APP_URL','APP_KEY',
            'DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS',
            'SESSION_NAME','SESSION_LIFETIME','COOKIE_SECURE','COOKIE_SAMESITE',
            'RATE_LIMIT_LOGIN_ATTEMPTS','RATE_LIMIT_LOGIN_WINDOW',
            'RATE_LIMIT_FLAG_ATTEMPTS','RATE_LIMIT_FLAG_WINDOW',
            'FEATURE_INSECURE_DEMOS','FEATURE_ADMIN_PANEL','FEATURE_FILE_UPLOAD_LAB','FEATURE_MFA_PLACEHOLDER',
            'UPLOAD_MAX_BYTES','UPLOAD_ALLOWED_EXT','LOG_LEVEL','LOG_RETENTION_DAYS','AUDIT_IP_PEPPER',
        ];
        $out = [];
        foreach ($keys as $k) {
            $out[strtolower($k)] = getenv($k);
        }
        return $out;
    }
}
