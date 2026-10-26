<?php

declare(strict_types=1);

namespace MG\Models;

use PDO;

/** Flag - yalnızca hash okunur; doğrulama FlagService'te sabit zamanlı yapılır. */
final class Flag
{
    /** @return array<int,array{lab_module_id:int,flag_hash:string}> */
    public static function hashesForLab(PDO $pdo, int $labId): array
    {
        $stmt = $pdo->prepare('SELECT lab_module_id, flag_hash FROM flags WHERE lab_module_id = ?');
        $stmt->execute([$labId]);
        return $stmt->fetchAll();
    }
}
