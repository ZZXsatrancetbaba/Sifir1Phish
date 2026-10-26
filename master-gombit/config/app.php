<?php

declare(strict_types=1);

/** Uygulama genel ayarlar (env öncelikli; burada statik varsayılanlar). */
return [
    'name' => getenv('APP_NAME') ?: 'Master Gombit',
    'env' => getenv('APP_ENV') ?: 'local',
    'url' => getenv('APP_URL') ?: 'http://127.0.0.1:8080',
    // Detaylı hata SADECE lokal bind + APP_DEBUG=true; production'da daima kapalı
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
];
