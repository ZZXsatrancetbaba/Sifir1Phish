<?php

declare(strict_types=1);

namespace MG\Models;

use PDO;

/** Submission - cevap yalnızca HASH olarak yazılır; ham cevap DB'ye düşmez. */
final class Submission
{
    public static function record(PDO $pdo, int $userId, int $labId, string $answerHash, string $status, int $score): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO submissions (user_id, lab_module_id, submitted_answer_hash, status, score)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $labId, $answerHash, $status, $score]);
    }

    /** @return array<int,array<string,mixed>> kullanıcının lab bazlı en iyi sonucu */
    public static function bestByUser(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare(
            'SELECT lab_module_id, MAX(score) AS best_score,
                    SUM(status = \'correct\') > 0 AS solved,
                    COUNT(*) AS attempts
               FROM submissions WHERE user_id = ? GROUP BY lab_module_id'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
