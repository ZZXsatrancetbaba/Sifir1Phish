<?php

declare(strict_types=1);

namespace MG\Security;

use MG\Core\Config;

/**
 * FeatureFlags - env tabanlı, beyaz listeli bayrak okuma.
 * Kritik kural: Zafiyetli demo bayrağı (INSECURE_DEMOS) yalnızca
 *  1) env'de true ise VE
 *  2) istek gerçekten localhost/docker iç bind üzerinden geldiyse (isLocalBind)
 * aktiftir. Banner her zaman gösterilir (View katmanı zorunlu tutar).
 */
final class FeatureFlags
{
    public const INSECURE_DEMOS = 'feature_insecure_demos';
    public const ADMIN_PANEL    = 'feature_admin_panel';
    public const FILE_UPLOAD_LAB = 'feature_file_upload_lab';
    public const MFA_PLACEHOLDER = 'feature_mfa_placeholder';

    public function enabled(string $flag): bool
    {
        if (!in_array($flag, self::allowed(), true)) {
            return false; // bilinmeyen bayrak = kapalı (fail-closed)
        }
        $value = filter_var((string)Config::get("env.{$flag}", 'false'), FILTER_VALIDATE_BOOLEAN);
        if ($flag === self::INSECURE_DEMOS && $value) {
            return self::isLocalBind();     // ekstra kilit: sadece lokal bind
        }
        return $value;
    }

    /** @return string[] */
    public static function allowed(): array
    {
        return [self::INSECURE_DEMOS, self::ADMIN_PANEL, self::FILE_UPLOAD_LAB, self::MFA_PLACEHOLDER];
    }

    /**
     * Sunucuya gelen bağlantının lokal/izole olup olmadığı.
     * REMOTE_ADDR özel/loopback aralıklarındaysa true.
     */
    public static function isLocalBind(): bool
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        if ($ip === '') {
            return true; // CLI / healthcheck
        }
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;   // private/loopback/reserved => lokal kabul
    }
}
