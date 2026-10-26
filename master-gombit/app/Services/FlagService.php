<?php

declare(strict_types=1);

namespace MG\Services;

use MG\Core\Config;
use MG\Models\Flag;
use MG\Models\Submission;
use MG\Security\AuditLogger;
use MG\Security\RateLimiter;
use PDO;

/**
 * FlagService - eğitim bayrağı doğrulama.
 * Kurallar:
 *  - Bayrak DB'de SHA-256 hash; gelen cevap hash'lenip hash_equals ile kıyaslanır.
 *  - Yanlış denemeler rate-limit'e tabi (brute-force bayrak tahmini engellenir).
 *  - Çözümde ham bayrak geri DÖNDÜRÜLMEZ; yalnızca "doğru" + rehber gösterilir.
 *  - Skor: kolay 10 / orta 20 / zor 30.
 */
final class FlagService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly RateLimiter $limiter,
        private readonly AuditLogger $audit,
    ) {}

    /** @param array<string,mixed> $lab @return bool */
    public function checkAndRecord(int $userId, array $lab, string $answer, string $ip, string $ua): bool
    {
        $max = (int)Config::get('env.rate_limit_flag_attempts', 10);
        $win = (int)Config::get('env.rate_limit_flag_window', 300);
        $bucket = sprintf('flag:user:%d:lab:%d', $userId, (int)$lab['id']);
        if ($this->limiter->exceeded($bucket, $max, $win)) {
            $this->audit->log($userId, 'flag_submit', $ip, $ua, (string)$lab['slug'], 'blocked');
            return false;   // blocked => controller "çok fazla deneme" mesajı verir
        }

        $candidateHash = hash('sha256', trim($answer));
        $correct = false;
        foreach (Flag::hashesForLab($this->pdo, (int)$lab['id']) as $f) {
            if (hash_equals((string)$f['flag_hash'], $candidateHash)) {
                $correct = true;
                break;
            }
        }

        $scoreMap = ['easy' => 10, 'medium' => 20, 'hard' => 30];
        $score = $correct ? ($scoreMap[(string)$lab['difficulty']] ?? 10) : 0;

        Submission::record(
            $this->pdo, $userId, (int)$lab['id'],
            $candidateHash,                 // ham cevap DEĞİL, hash saklanır
            $correct ? 'correct' : 'incorrect',
            $score
        );
        $this->audit->log($userId, 'flag_submit', $ip, $ua, (string)$lab['slug'], $correct ? 'success' : 'failure');

        if ($correct) {
            $this->awardBadge((int)$lab['id'], $userId);
            $this->limiter->clear($bucket);
        }
        return $correct;
    }

    private function awardBadge(int $labId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO user_badges (user_id, badge_id)
             SELECT ?, id FROM badges WHERE lab_module_id = ?'
        );
        $stmt->execute([$userId, $labId]);
    }
}
