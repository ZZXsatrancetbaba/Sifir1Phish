<?php

declare(strict_types=1);

namespace MG\Services;

use MG\Models\Submission;
use MG\Models\User;
use PDO;

/**
 * ReportService - öğrenci ilerlemesini jenerik JSON/CSV rapora çevirir.
 * Dışa aktarımda PII azaltma: e-posta alanı YOK, yalnız username + skor.
 */
final class ReportService
{
    public function __construct(private readonly PDO $pdo) {}

    /** @return array<string,mixed> */
    public function progressFor(User $user): array
    {
        $rows = Submission::bestByUser($this->pdo, $user->id);
        $totalScore = 0;
        $perOwasp = [];
        foreach ($rows as $r) {
            $totalScore += (int)$r['best_score'];
            $owasp = $this->owaspOf((int)$r['lab_module_id']);
            $perOwasp[$owasp] = ($perOwasp[$owasp] ?? 0) + (int)$r['best_score'];
        }
        $badges = $this->pdo->prepare(
            'SELECT b.code, b.label FROM user_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.user_id = ?'
        );
        $badges->execute([$user->id]);
        return [
            'username' => $user->username,
            'total_score' => $totalScore,
            'by_owasp' => $perOwasp,
            'badges' => $badges->fetchAll(),
            'weak_areas' => $this->weakAreas($perOwasp),
        ];
    }

    private function owaspOf(int $labId): string
    {
        $stmt = $this->pdo->prepare('SELECT owasp_category FROM lab_modules WHERE id = ? LIMIT 1');
        $stmt->execute([$labId]);
        return (string)($stmt->fetchColumn() ?: 'A00');
    }

    /** @param array<string,int> $perOwasp @return string[] */
    private function weakAreas(array $perOwasp): array
    {
        $weak = [];
        foreach ($perOwasp as $cat => $score) {
            if ($score < 20) { $weak[] = $cat; }
        }
        return $weak;
    }

    public function toCsv(array $progress): string
    {
        $lines = ['section,value'];
        $lines[] = 'username,' . $progress['username'];
        $lines[] = 'total_score,' . $progress['total_score'];
        foreach ($progress['by_owasp'] as $k => $v) {
            $lines[] = "owasp_{$k},{$v}";
        }
        foreach ($progress['badges'] as $b) {
            $lines[] = 'badge,' . $b['code'];
        }
        return implode("\n", $lines) . "\n";
    }
}
