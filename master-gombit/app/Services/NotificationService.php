<?php

declare(strict_types=1);

namespace MG\Services;

/**
 * NotificationService - yer tutucu (placeholder).
 * Eğitim laboratuvası dış sistemlere (e-posta/Telegram/webhook) BAĞLANMAZ.
 * Gerçek projede burada kuyruk tabanlı, rate-limit'li bildirim katmanı olur.
 */
final class NotificationService
{
    /** @param array<string,mixed> $payload */
    public function notify(string $channel, string $message, array $payload = []): void
    {
        // Lokal log'a yaz; ağa çıkış yok (bilerek).
        error_log(sprintf('[MG][notify:%s] %s %s', $channel, $message, json_encode($payload, JSON_UNESCAPED_UNICODE)));
    }
}
