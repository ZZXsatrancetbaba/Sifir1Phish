<?php

declare(strict_types=1);

namespace MG\Security;

use PDO;

/**
 * RateLimiter - DB tabanlı sabit pencere limiter (eğitim amaçlı minimal).
 * Production önerisi: Redis + token bucket (docs/RUNBOOK.md).
 * Bucket key örnekleri:
 *   login:user:alice , login:ip:<hash> , flag:user:{id}:lab:{id}
 * Başarısız login hem kullanıcı hem IP boyutunda sayılır (credential stuffing yavaşlatma).
 */
final class RateLimiter
{
    public function __construct(private readonly PDO $pdo) {}

    /** @return bool true => limit AŞILDI (istek engellenmeli) */
    public function exceeded(string $bucketKey, int $maxAttempts, int $windowSeconds): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT hits, window_start FROM rate_limits WHERE bucket_key = ? LIMIT 1'
        );
        $stmt->execute([$bucketKey]);
        $row = $stmt->fetch();

        $now = time();
        if ($row === false) {
            $ins = $this->pdo->prepare(
                'INSERT INTO rate_limits (bucket_key, hits, window_start) VALUES (?, 1, NOW())'
                . ' ON DUPLICATE KEY UPDATE hits = hits + 1'
            );
            $ins->execute([$bucketKey]);
            return false;
        }

        $startTs = strtotime((string)$row['window_start']) ?: $now;
        if (($now - $startTs) > $windowSeconds) {
            // Pencere doldu: sıfırla
            $rst = $this->pdo->prepare(
                'UPDATE rate_limits SET hits = 1, window_start = NOW() WHERE bucket_key = ?'
            );
            $rst->execute([$bucketKey]);
            return false;
        }

        $inc = $this->pdo->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE bucket_key = ?');
        $inc->execute([$bucketKey]);
        return ((int)$row['hits'] + 1) > $maxAttempts;
    }

    public function clear(string $bucketKey): void
    {
        $del = $this->pdo->prepare('DELETE FROM rate_limits WHERE bucket_key = ?');
        $del->execute([$bucketKey]);
    }
}
