<?php

declare(strict_types=1);

namespace MG\Controllers;

use MG\Core\Config;
use MG\Core\Container;
use MG\Core\Request;
use MG\Core\Response;
use MG\Security\CsrfTokenManager;
use MG\Security\FeatureFlags;
use MG\Security\OutputEncoder as E;

/** HomeController - landing + lab kataloğu girişi. */
final class HomeController
{
    public function index(Request $req, array $params, Container $c): Response
    {
        /** @var CsrfTokenManager $csrf */
        $csrf = $c->get(CsrfTokenManager::class);
        /** @var FeatureFlags $flags */
        $flags = $c->get(FeatureFlags::class);
        $labs = \MG\Models\LabModule::listEnabled($c->get(\PDO::class), $flags);

        $data = [
            'title' => 'Master Gombit',
            'csrf' => $csrf->token(),
            'labs' => $labs,
            'insecureBanner' => $flags->enabled(FeatureFlags::INSECURE_DEMOS),
            'appName' => (string)Config::get('env.app_name', 'Master Gombit'),
        ];
        return (new Response())->body(self::render(__DIR__ . '/../../app/Views/home.php', $data));
    }

    /** Basit view renderer (layout include). */
    public static function render(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }
}
