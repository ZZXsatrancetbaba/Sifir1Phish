<?php

declare(strict_types=1);

namespace MG\Models;

use PDO;

/** AuditLog - salt-read sorguları (admin.audit permission ile erişilir). */
final class AuditLog
{
    /** @return array<int,array<string,mixed>> */
    public static function recent(PDO $pdo, int $limit = 50, string $eventType = ''): array
    {
        // LIMIT parametre olarak bağlanamaz (bazı MySQL sürümleri) -> int cast + whitelist
        $limit = max(1, min(200, $limit));
        if ($eventType !== '') {
            $stmt = $pdo->prepare(
                'SELECT id, user_id, event_type, ip_hash, target, result, created_at
                   FROM audit_logs WHERE event_type = ? ORDER BY id DESC LIMIT ' . $limit
            );
            $stmt->execute([$eventType]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, user_id, event_type, ip_hash, target, result, created_at
                   FROM audit_logs ORDER BY id DESC LIMIT ' . $limit
            );
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
}
