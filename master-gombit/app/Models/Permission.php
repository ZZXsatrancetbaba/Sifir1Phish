<?php

declare(strict_types=1);

namespace MG\Models;

use PDO;

/** Permission - isim bazlı whitelist; route etiketleriyle eşleşir. */
final class Permission
{
    public const LAB_VIEW = 'lab.view';
    public const LAB_SUBMIT = 'lab.submit';
    public const NOTE_OWN = 'note.own';
    public const FILE_UPLOAD = 'file.upload';
    public const FILE_DOWNLOAD_OWN = 'file.download.own';
    public const REPORT_EXPORT = 'report.export';
    public const ADMIN_USERS = 'admin.users';
    public const ADMIN_AUDIT = 'admin.audit';
    public const ADMIN_FLAGS = 'admin.flags';
    public const DEMO_INSECURE = 'demo.insecure';

    /** @return string[] */
    public static function names(PDO $pdo): array
    {
        return array_map('strval', $pdo->query('SELECT name FROM permissions ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    }
}
