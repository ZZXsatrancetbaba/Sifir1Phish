<?php

declare(strict_types=1);

namespace MG\Tests\Feature;

use MG\Security\RateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * RateLimiter feature testi: DB varsa koşar, yoksa atlanır.
 * SQLite in-memory ile aynı SQL semantiği test edilemez (ON DUPLICATE KEY);
 * bu yüzden test MySQL/MariaDB'e bağlanır (docker compose içinde çalıştırın):
 *   docker compose exec app php vendor/bin/phpunit --testsuite Feature
 */
final class RateLimitTest extends TestCase
{
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'db';
        try {
            $this->pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, getenv('DB_NAME') ?: 'mg_lab'),
                (string)getenv('DB_USER'),
                (string)getenv('DB_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Throwable) {
            self::markTestSkipped('Veritabanı erişilebilir değil (lokal Docker gerekir).');
        }
    }

    public function testBlocksAfterMaxAttemptsAndClearResets(): void
    {
        $key = 'test:bucket:' . bin2hex(random_bytes(4));
        $rl = new RateLimiter($this->pdo);
        self::assertFalse($rl->exceeded($key, 3, 60));   // 1
        self::assertFalse($rl->exceeded($key, 3, 60));   // 2
        self::assertFalse($rl->exceeded($key, 3, 60));   // 3
        self::assertTrue($rl->exceeded($key, 3, 60));    // 4 -> aşım
        $rl->clear($key);
        self::assertFalse($rl->exceeded($key, 3, 60));   // reset sonrası temiz
        $this->pdo->exec('DELETE FROM rate_limits WHERE bucket_key = ' . $this->pdo->quote($key));
    }
}
