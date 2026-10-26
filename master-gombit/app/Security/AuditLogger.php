<?php

declare(strict_types=1);

namespace MG\Security;

use MG\Core\Config;
use PDO;
use Throwable;

/**
 * AuditLogger - yapılandırılmış denetim kaydı.
 * GÜVENLİK KURALLARI:
 *  - IP ve User-Agent HAM saklanmaz: sha256(pepper + value).
 *  - metadata içinde parola/token/flag ASLA loglanmaz -> redactList ile maskelenir.
 *  - Log yazımı uygulama hatasını DURDURMAZ (try/catch fail-open logging,
 *    ama hata error_log'a düşer).
 * Olaylar: login_ok, login_fail, logout, flag_submit, upload, admin_action, perm_change
 */
final class AuditLogger
{
    private const REDACT_KEYS = ['password','pass','token','csrf_token','flag','answer','secret','authorization'];

    public function __construct(private readonly PDO $pdo, private readonly object $container) {}

    /** @param array<string,mixed> $metadata */
    public function log(?int $userId, string $eventType, string $ip, string $userAgent, string $target, string $result, array $metadata = []): void
    {
        try {
            $meta = self::redact($metadata);
            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_logs (user_id, event_type, ip_hash, user_agent_hash, target, result, metadata_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                substr($eventType, 0, 48),
                self::pepperedHash($ip),
                self::pepperedHash(substr($userAgent, 0, 256)),
                substr($target, 0, 128),
                in_array($result, ['success','failure','blocked'], true) ? $result : 'failure',
                $meta === [] ? null : json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable $e) {
            error_log('[MG][audit-write-fail] ' . $e->getMessage());
        }
    }

    /** @param array<string,mixed> $arr @return array<string,mixed> */
    public static function redact(array $arr): array
    {
        foreach ($arr as $k => $v) {
            if (in_array(strtolower((string)$k), self::REDACT_KEYS, true)) {
                $arr[$k] = '[REDACTED]';
            } elseif (is_array($v)) {
                $arr[$k] = self::redact($v);
            }
        }
        return $arr;
    }

    public static function pepperedHash(string $value): string
    {
        $pepper = (string)Config::get('env.audit_ip_pepper', 'dev-pepper-change-me');
        return hash('sha256', $pepper . '|' . $value);
    }
}
