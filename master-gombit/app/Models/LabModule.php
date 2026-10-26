<?php

declare(strict_types=1);

namespace MG\Models;

use MG\Security\FeatureFlags;
use PDO;

/**
 * LabModule - lab katalog erişimi.
 * Güvenlik notları:
 *  - requires_feature_flag olan modüller bayrak kapalıyça SORGUDA da filtrelenir
 *    (UI'da gizlemek yetmez; sunucu tarafı zorunlu - Fail Closed).
 */
final class LabModule
{
    /** @return array<int,array<string,mixed>> */
    public static function listEnabled(PDO $pdo, FeatureFlags $flags): array
    {
        $rows = $pdo->query(
            'SELECT id, slug, title, description, owasp_category, difficulty, requires_feature_flag
               FROM lab_modules WHERE is_enabled = 1 ORDER BY id'
        )->fetchAll();

        return array_values(array_filter($rows, static function (array $r) use ($flags): bool {
            $flag = $r['requires_feature_flag'];
            return $flag === null || $flags->enabled(strtolower((string)$flag));
        }));
    }

    /** @return array<string,mixed>|null */
    public static function bySlug(PDO $pdo, string $slug, FeatureFlags $flags): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, slug, title, description, owasp_category, difficulty, requires_feature_flag
               FROM lab_modules WHERE slug = ? AND is_enabled = 1 LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row === false) { return null; }
        $flag = $row['requires_feature_flag'];
        if ($flag !== null && !$flags->enabled(strtolower((string)$flag))) { return null; }
        return (array)$row;
    }
}
