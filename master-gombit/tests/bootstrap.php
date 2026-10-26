<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap - autoload + test env defaults (DB testleri in-memory/mock;
 * Feature testleri DB isterse MG_TEST_DB_* env'ine bakar; yoksa atlanır).
 */
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "composer install çalıştırılmamış; unit testler vendor olmadan da koşabilir (fallback PSR-4 loader).\n");
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'MG\\')) {
            $rel = str_replace('\\', '/', substr($class, 3));
            $file = __DIR__ . '/../app/' . $rel . '.php';
            if (is_file($file)) { require $file; }
        }
    });
} else {
    require $autoload;
}

putenv('APP_ENV=local');
putenv('AUDIT_IP_PEPPER=test-pepper');
