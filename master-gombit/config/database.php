<?php

declare(strict_types=1);

/** Veritabanı bağlantı varsayılanları — gerçek değerler env'den gelir (App::boot). */
return [
    'host' => getenv('DB_HOST') ?: 'db',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'mg_lab',
    'charset' => 'utf8mb4',
    // Eğitim notu: uygulama hesabında DDL/GRANT yetkisi OLMAMALI (least privilege).
];
