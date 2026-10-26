<?php

declare(strict_types=1);

namespace MG\Security;

/**
 * OutputEncoder - bağlama duyarlı kodlama (context-aware encoding).
 * OWASP XSS Prevention Cheat Sheet referansı:
 *  - HTML body      -> escHtml()
 *  - HTML attribute -> escAttr() (aynı htmlspecialchars + quote)
 *  - JS string      -> json_encode (hex flags) - asla raw gömme
 *  - URL            -> rawurlencode
 *  - JSON API       -> Response::json (Content-Type application/json)
 * Tüm view'lar bu sınıfı kullanır; template'te `<?= $x ?>` ham çıktı YASAK.
 */
final class OutputEncoder
{
    public static function escHtml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public static function escAttr(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /** <script> içine veri gömerken: JSON + </ → <\/ koruması (script breakout önleme). */
    public static function escJs(mixed $data): string
    {
        $json = json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        return $json;
    }

    public static function escUrl(string $s): string
    {
        return rawurlencode($s);
    }

    /** E-posta/URL gibi görünen düz metni güvenli şekilde linkleştirmek yerine
     *  SADECE https:// şemasında, rel=noopener noreferrer ile ver. (Link hijack azaltma) */
    public static function safeLink(string $url): ?string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) { return null; }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return $scheme === 'https' ? self::escAttr($url) : null;
    }
}
