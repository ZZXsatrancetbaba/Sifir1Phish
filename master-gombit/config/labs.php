<?php

declare(strict_types=1);

/** Lab katalog meta verisi (DB'deki lab_modules ile paralel, dokümantasyon amaçlı). */
return [
    'order' => ['environment','sql-awareness','xss-awareness','csrf-awareness','auth-session',
                'authorization-idor','file-upload','logging-monitoring','secrets-dependencies','reporting'],
    'scores' => ['easy' => 10, 'medium' => 20, 'hard' => 30],
];
