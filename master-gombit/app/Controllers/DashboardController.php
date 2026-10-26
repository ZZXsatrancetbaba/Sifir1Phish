<?php

declare(strict_types=1);

namespace MG\Controllers;

use MG\Core\Container;
use MG\Core\Request;
use MG\Core\Response;
use MG\Exceptions\AppException;
use MG\Security\CsrfTokenManager;
use MG\Security\FeatureFlags;
use MG\Services\LabService;
use MG\Services\ReportService;

/**
 * DashboardController - profil kartı, puan, OWASP ilerleme, rozetler, rapor export.
 * Tüm veri nesne-düzeyi owner filtreli (session user_id).
 */
final class DashboardController
{
    public function index(Request $req, array $params, Container $c): Response
    {
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null) {
            throw new AppException('Giriş gerekli.', 401);
        }
        /** @var ReportService $rs */
        $rs = $c->get(ReportService::class);
        $progress = $rs->progressFor($user);
        /** @var CsrfTokenManager $csrf */
        $csrf = $c->get(CsrfTokenManager::class);
        /** @var FeatureFlags $flags */
        $flags = $c->get(FeatureFlags::class);

        return (new Response())->body(HomeController::render(__DIR__ . '/../../app/Views/dashboard.php', [
            'title' => 'Panel',
            'user' => $user,
            'progress' => $progress,
            'csrf' => $csrf->token(),
            'insecureBanner' => $flags->enabled(FeatureFlags::INSECURE_DEMOS),
        ]));
    }

    /** JSON rapor (API örneği): Content-Type application/json -> tarayıcı render etmez. */
    public function reportJson(Request $req, array $params, Container $c): Response
    {
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null) { throw new AppException('Giriş gerekli.', 401); }
        /** @var ReportService $rs */
        $rs = $c->get(ReportService::class);
        return (new Response())->json($rs->progressFor($user))->header('Cache-Control', 'no-store');
    }

    public function reportCsv(Request $req, array $params, Container $c): Response
    {
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null) { throw new AppException('Giriş gerekli.', 401); }
        /** @var ReportService $rs */
        $rs = $c->get(ReportService::class);
        return (new Response())
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="progress.csv"')
            ->body($rs->toCsv($rs->progressFor($user)));
    }

    /** Dosya indirme: kontrollü delivery (webroot dışı + owner check + inline olamaz). */
    public function downloadFile(Request $req, array $params, Container $c): Response
    {
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null) { throw new AppException('Giriş gerekli.', 401); }
        /** @var LabService $labs */
        $labs = $c->get(LabService::class);
        $file = $labs->getOwnFile($user, (int)($params['id'] ?? 0));
        if ($file === null) {
            throw new AppException('Dosya bulunamadı.', 404);   // "senin değil" yerine jenerik (info sızdırma yok)
        }
        $path = dirname(__DIR__, 2) . '/storage/uploads/' . basename((string)$file['stored_path_random']);
        if (!is_file($path)) { throw new AppException('Dosya bulunamadı.', 404); }

        return (new Response())
            ->header('Content-Type', (string)$file['mime_type'])
            ->header('Content-Disposition', 'attachment; filename="' . str_replace('"', '', (string)$file['original_name_sanitized']) . '"')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-store')
            ->body((string)file_get_contents($path));
    }
}
