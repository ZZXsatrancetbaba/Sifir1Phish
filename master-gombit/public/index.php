<?php

declare(strict_types=1);

/**
 * Master Gombit - Front Controller
 * Amaç: TEK giriş noktası. Tüm istekler buradan Router'a akar.
 * Güvenlik notları:
 *  - declare(strict_types=1) tip karışıklığı zafiyetlerini azaltır.
 *  - Hata gösterimi kapalı; centralized ErrorHandler karar verir.
 *  - Session cookie'leri hardened (HttpOnly/SameSite) başlatılır.
 */

use MG\Core\App;

// Composer autoload (PSR-4: MG\ => app/)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    // Bağımlılıklar kurulmamış: bilgi sızmmadan jenerik mesaj
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Master Gombit: bağımlılıklar eksik. 'composer install' çalıştırın (lokal).";
    exit;
}
require $autoload;

App::boot(dirname(__DIR__))->run();
