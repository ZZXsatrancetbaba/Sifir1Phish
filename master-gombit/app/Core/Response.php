<?php

declare(strict_types=1);

namespace MG\Core;

/**
 * Response - tek çıkış noktası. Body'ye yazmadan önce Content-Type ve
 * encoding sorumluluğu view katmanındadır (htmlspecialchars).
 */
final class Response
{
    private int $status = 200;
    /** @var array<string,string> */
    private array $headers = [];
    private string $body = '';

    public function status(int $code): self { $this->status = $code; return $this; }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function body(string $html): self { $this->body = $html; return $this; }

    public function json(mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR): self
    {
        $this->headers['Content-Type'] = 'application/json; charset=UTF-8';
        $this->body = json_encode($data, $flags);
        return $this;
    }

    public function redirect(string $to, int $code = 302): self
    {
        // Açık yönlendirme (open redirect) koruması: yalnızca relatif yola izin
        if (!str_starts_with($to, '/') || str_starts_with($to, '//')) {
            $to = '/';
        }
        $this->status = $code;
        $this->headers['Location'] = $to;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $k => $v) {
            header("{$k}: {$v}");
        }
        echo $this->body;
    }
}
