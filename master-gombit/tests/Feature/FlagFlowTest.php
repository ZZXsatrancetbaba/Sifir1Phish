<?php

declare(strict_types=1);

namespace MG\Tests\Feature;

use MG\Core\Request;
use MG\Services\FlagService;
use MG\Security\AuditLogger;
use MG\Security\RateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Bayrak doğrulama akışı: hash-only kontrol + yanlış cevapta ham veri saklanmadığı.
 * DB gerekli; yoksa skip (yukarıdaki gibi container içinde koşun).
 */
final class FlagFlowTest extends TestCase
{
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        try {
            $this->pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: 'db', getenv('DB_NAME') ?: 'mg_lab'),
                (string)getenv('DB_USER'), (string)getenv('DB_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Throwable) {
            self::markTestSkipped('DB yok.');
        }
    }

    public function testCorrectHashAcceptedAndRawAnswerNeverStored(): void
    {
        $flag = 'MG{pr3p4r3d-st4t3m3nts-4lw4ys}';       // seed'deki lab 2 sentetik bayrağı
        $lab = ['id' => 2, 'slug' => 'sql-awareness', 'difficulty' => 'medium'];
        $svc = new FlagService($this->pdo, new RateLimiter($this->pdo),
            new AuditLogger($this->pdo, new class { public function get(string $i): object { throw new \RuntimeException('n/a'); } }));

        // Test kullanıcısı 1 (student). Deneme öncesi sayaç temizle.
        $this->pdo->exec("DELETE FROM submissions WHERE user_id=1 AND lab_module_id=2");
        $ok = $svc->checkAndRecord(1, $lab, $flag, '127.0.0.1', 'phpunit');
        self::assertTrue($ok);

        $row = $this->pdo->query(
            "SELECT submitted_answer_hash FROM submissions WHERE user_id=1 AND lab_module_id=2 ORDER BY id DESC LIMIT 1"
        )->fetch();
        self::assertNotFalse($row);
        self::assertSame(hash('sha256', $flag), $row['submitted_answer_hash']);
        self::assertStringNotContainsString('MG{', (string)$row['submitted_answer_hash']); // ham cevap YOK
    }
}
