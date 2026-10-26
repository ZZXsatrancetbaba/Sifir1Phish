<?php

declare(strict_types=1);

namespace MG\Security;

use MG\Core\Config;
use PDO;

/**
 * InputValidator - beyaz liste yaklaşımı ile girdi doğrulama.
 * Kurallar:
 *  - username: [a-z0-9_.-]{3,64} (küçük harfe normalize)
 *  - email: FILTER_VALIDATE_EMAIL + uzunluk
 *  - flag/cevap: baskılanabilir ASCII, max 128
 *  - note content: çok satırlı serbest metin AMA uzunlık sınırlı; output encoding view'da
 *  - Uzun input => reddetme (regex DoS ve bellek tüketimi önleme)
 */
final class InputValidator
{
    public static function username(string $raw): ?string
    {
        $v = mb_strtolower(trim($raw));
        if (mb_strlen($v) > 64) { return null; }
        return preg_match('/^[a-z0-9._-]{3,64}$/', $v) === 1 ? $v : null;
    }

    public static function email(string $raw): ?string
    {
        $v = trim($raw);
        if (mb_strlen($v) > 254) { return null; }
        return filter_var($v, FILTER_VALIDATE_EMAIL) !== false ? mb_strtolower($v) : null;
    }

    /** Bayrak/cevap alanı: yalnızca görünür ASCII, boşluksuz, makul uzunluk. */
    public static function answer(string $raw): ?string
    {
        $v = trim($raw);
        if (mb_strlen($v) > 128) { return null; }
        return preg_match('/^[\x21-\x7E]+$/', $v) === 1 ? $v : null;
    }

    /** Not içeriği: kontrol karakterleri temizlenir, uzunluk limitlenir. HTML üretilmez. */
    public static function noteContent(string $raw): ?string
    {
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw);
        if ($v === null || mb_strlen($v) > 10000) { return null; }
        return trim($v);
    }

    /** Slug whitelist (lab erişiminde serbest slug kabul edilmez). */
    public static function slug(string $raw): ?string
    {
        $v = trim($raw);
        return preg_match('/^[a-z0-9-]{2,64}$/', $v) === 1 ? $v : null;
    }

    /** Dinamik ORDER BY gibi yerlerde TABLO/KOLON adı whitelist örneği (Lab 02 dersi). */
    public static function oneOf(string $raw, array $whitelist): ?string
    {
        return in_array($raw, $whitelist, true) ? $raw : null;
    }
}
