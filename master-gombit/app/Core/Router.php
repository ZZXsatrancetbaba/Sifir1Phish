<?php

declare(strict_types=1);

namespace MG\Core;

use MG\Exceptions\AppException;

/**
 * Router - minimal, whitelist tabanlı rota eşleştirici.
 * Güvenlik notları:
 *  - Rota parametreleri ([id]) yalnızca int olarak yakalanır (IDOR girdisi daraltma).
 *  - Eşleşmeyen rota = 404 (varsayılan dosya sunumu YOK).
 *  - Her rotaya permission etiketi verilir; App middleware'i zorlar.
 */
final class Router
{
    /** @var array<int,array{method:string,pattern:string,regex:string,handler:callable,permission:?string}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, ?string $permission = null): void
    {
        // [name] -> named int capture; geri kalan literal
        $regex = preg_replace_callback('/\{([a-z_]+)\}/', static fn($m) => '(?P<' . $m[1] . '>[0-9]{1,10})', $pattern);
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'permission' => $permission,
        ];
    }

    public function get(string $p, callable $h, ?string $perm = null): void { $this->add('GET', $p, $h, $perm); }
    public function post(string $p, callable $h, ?string $perm = null): void { $this->add('POST', $p, $h, $perm); }

    /** @return array{handler:callable,params:array<string,string>,permission:?string} */
    public function match(string $method, string $path): array
    {
        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) { continue; }
            if (preg_match($r['regex'], $path, $m) === 1) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                return ['handler' => $r['handler'], 'params' => $params, 'permission' => $r['permission']];
            }
        }
        // Method kontrolü: path var ama method farklıysa 405 mantığı için işaret
        throw new AppException('Sayfa bulunamadı', 404);
    }
}
