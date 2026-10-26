<?php

declare(strict_types=1);

namespace MG\Core;

use MG\Exceptions\AppException;
use MG\Security\SessionGuard;
use MG\Security\CsrfTokenManager;
use MG\Security\SecurityHeaders;
use MG\Security\FeatureFlags;
use MG\Security\AuditLogger;
use MG\Models\User;
use PDO;
use Throwable;

/**
 * App - boot + middleware pipeline + dispatch + centralized error handling.
 * Akış:
 *   Request -> SecurityHeaders -> Session -> Router match -> CSRF(POST)
 *   -> Permission check -> Controller -> Response -> send
 * Hatalar:
 *   AppException -> uygun HTTP kodu + jenerik view; diğerleri -> 500 (detay log'a).
 */
final class App
{
    private function __construct(
        private readonly Container $c,
        private readonly string $rootPath,
    ) {}

    public static function boot(string $rootPath): self
    {
        Config::load($rootPath);
        $c = new Container();

        $c->set(PDO::class, static function (): PDO {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                (string)Config::get('env.db_host', 'db'),
                (string)Config::get('env.db_port', '3306'),
                (string)Config::get('env.db_name', 'mg_lab'),
            );
            return new PDO($dsn, (string)Config::get('env.db_user', ''), (string)Config::get('env.db_pass', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // exception, sessiz hata yok
                PDO::ATTR_EMULATE_PREPARES => false,               // GERÇEK prepared statement
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        });

        $c->set(Router::class, static fn() => new Router());
        $c->set(SessionGuard::class, static fn($c) => new SessionGuard($c));
        $c->set(CsrfTokenManager::class, static fn($c) => new CsrfTokenManager($c->get(SessionGuard::class)));
        $c->set(FeatureFlags::class, static fn() => new FeatureFlags());
        $c->set(SecurityHeaders::class, static fn() => new SecurityHeaders());
        $c->set(AuditLogger::class, static fn($c) => new AuditLogger($c->get(PDO::class), $c));

        $app = new self($c, $rootPath);
        (require $rootPath . '/config/routes.php')($c->get(Router::class), $app);
        return $app;
    }

    public function container(): Container { return $this->c; }

    public function run(): void
    {
        $request = Request::fromGlobals();
        try {
            /** @var SecurityHeaders $headers */
            $headers = $this->c->get(SecurityHeaders::class);
            $headers->apply();

            /** @var SessionGuard $session */
            $session = $this->c->get(SessionGuard::class);
            $session->start();
            $session->expireIfNeeded();

            /** @var Router $router */
            $router = $this->c->get(Router::class);
            $route = $router->match($request->method, $request->path);

            // State-changing isteklerde CSRF (SessionGuard içinde login hariç tutulmaz; token her POST'ta zorunlu)
            if ($request->method !== 'GET' && $request->method !== 'HEAD') {
                /** @var CsrfTokenManager $csrf */
                $csrf = $this->c->get(CsrfTokenManager::class);
                $csrf->validateOrFail($request);
            }

            // RBAC route-level kontrol
            if ($route['permission'] !== null) {
                $user = $session->currentUser();
                if ($user === null) {
                    $resp = (new Response())->redirect('/login');
                    $resp->send();
                    return;
                }
                if (!$user->hasPermission($route['permission'])) {
                    throw new AppException('Bu işlem için yetkiniz yok.', 403);
                }
            }

            $response = ($route['handler'])($request, $route['params'], $this->c);
            if (!$response instanceof Response) {
                throw new AppException('Controller geçersiz yanıt döndürdü', 500);
            }
            $response->send();
        } catch (AppException $e) {
            $this->renderError($e->getStatusCode(), $e->getMessage(), $request->path);
        } catch (Throwable $e) {
            error_log('[MG] Unhandled: ' . $e->getMessage()); // detay SADECE log'a
            $this->renderError(500, 'Beklenmeyen bir hata oluştu.', $request->path);
        }
    }

    private function renderError(int $status, string $message, string $path): void
    {
        http_response_code($status);
        $debug = filter_var((string)Config::get('env.app_debug', 'false'), FILTER_VALIDATE_BOOLEAN)
                 && FeatureFlags::isLocalBind();     // detay yalnız lokal bind + debug flag
        $safeMsg = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safePath = htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        require $this->rootPath . '/app/Views/errors/error.php';
    }
}
