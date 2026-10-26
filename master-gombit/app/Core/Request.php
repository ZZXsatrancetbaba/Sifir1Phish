<?php

declare(strict_types=1);

namespace MG\Core;

/**
 * Request - gelen HTTP isteğinin güvenli, salt-okunur sarmalayıcısı.
 * Güvenlik notları:
 *  - Tüm kullanıcı verisi buradan OKUNUR; controller'lar $_GET/$_POST direkt kullanmaz.
 *  - queryParam/bodyParam yalnızca scalar döner (dizi obj enjeksiyonunu kırpar).
 *  - IP ham verilmez; hash için AuditLogger'a gider.
 */
final class Request
{
    /** @param array<string,mixed> $query @param array<string,mixed> $body @param array<string,mixed> $files */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $files,
        public readonly array $headers,
        public readonly string $ip,
        public readonly string $userAgent,
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        // Normalizasyon: çift slash ve trailing slash temizliği (path traversal/route confusion önleme)
        $path = '/' . trim(preg_replace('#/+#', '/', $path) ?? '', '/');
        if ($path === '/') { $path = '/'; }

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = (string)$v;
            }
        }

        $body = $_POST;
        // JSON gövde desteği (AJAX CSRF header testi için)
        if (($headers['content-type'] ?? '') === 'application/json') {
            $raw = file_get_contents('php://input');
            $decoded = json_decode((string)$raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self(
            method: in_array($method, ['GET','POST','PUT','PATCH','DELETE','HEAD'], true) ? $method : 'GET',
            path: $path,
            query: $_GET,
            body: $body,
            files: $_FILES,
            headers: $headers,
            ip: (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            userAgent: (string)($headers['user-agent'] ?? ''),
        );
    }

    public function isPost(): bool { return $this->method === 'POST'; }

    /** Yalnızca scalar string param; dizi/obj reddedilir (mass assignment/HPP kırpma). */
    public function str(string $name, string $default = ''): string
    {
        $v = $this->body[$name] ?? $this->query[$name] ?? null;
        return is_string($v) || is_numeric($v) ? (string)$v : $default;
    }

    public function int(string $name, int $default = 0): int
    {
        $v = $this->body[$name] ?? $this->query[$name] ?? null;
        return is_numeric($v) ? (int)$v : $default;
    }

    public function header(string $name, string $default = ''): string
    {
        return (string)($this->headers[strtolower($name)] ?? $default);
    }

    /** @return array{name:string,tmp_name:string,size:int,error:int,type:string}|null */
    public function file(string $name): ?array
    {
        $f = $this->files[$name] ?? null;
        return (is_array($f) && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $f : null;
    }
}
