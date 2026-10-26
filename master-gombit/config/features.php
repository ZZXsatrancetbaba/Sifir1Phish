<?php

declare(strict_types=1);

/** Feature flag tanımları — tümü VARSAYILAN KAPALI; açmak env ile olur. */
return [
    'FEATURE_INSECURE_DEMOS' => false,   // zafiyetli demo anlatımı (UYARI banner'ı zorunlu)
    'FEATURE_ADMIN_PANEL' => false,      // admin paneli çift kapı
    'FEATURE_FILE_UPLOAD_LAB' => true,   // zararsız upload eğitimi
    'FEATURE_MFA_PLACEHOLDER' => true,   // MFA mimari yer tutucusu (uygulama yok)
];
