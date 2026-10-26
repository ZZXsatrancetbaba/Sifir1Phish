<?php

declare(strict_types=1);

/** Güvenlik parametreleri — merkezi, tek yerden yönetilir. */
return [
    'session' => [
        'idle_timeout' => 1800,       // 30 dk boşta
        'absolute_ttl' => 28800,      // 8 saat mutlak
    ],
    'login' => [
        'max_attempts' => 5,
        'window_seconds' => 600,
        'lockout_seconds' => 900,     // hesap kilidi
    ],
    'flags' => [
        'max_attempts' => 10,
        'window_seconds' => 300,
    ],
    'upload' => [
        'max_bytes' => 2097152,
        'allowed_ext' => ['png','jpg','gif','txt','pdf','md'],
        'storage' => 'storage/uploads',   // webroot DIŞINDA
    ],
    'csp' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'",
];
