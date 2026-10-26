<?php

declare(strict_types=1);

/**
 * Health check - Docker HEALTHCHECK için.
 * GÜVENLİK NOTLARI:
 *  - Sürüm, yol, DB detayı gibi BİLGİ SIZDIRMAZ. Jenerik JSON döner.
 *  - DB'ye yalnızca "1" seçer; hata olursa 503 + jenerik mesaj.
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

status:
{
    $ok = true;
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST') ?: 'db', getenv('DB_PORT') ?: '3306',
            getenv('DB_NAME') ?: 'mg_lab');
        $pdo = new PDO($dsn, getenv('DB_USER') ?: '', getenv('DB_PASS') ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2,
        ]);
        $pdo->query('SELECT 1')->fetchColumn();
    } catch (Throwable) {
        $ok = false; // detayı ASLA client'a yazma; sunucu loguna düşer
    }
    http_response_code($ok ? 200 : 503);
    echo json_encode(['status' => $ok ? 'ok' : 'unavailable']);
}
