<?php

declare(strict_types=1);

namespace MG\Controllers;

use MG\Core\Container;
use MG\Core\Request;
use MG\Core\Response;
use MG\Exceptions\AppException;
use MG\Models\AuditLog;
use MG\Security\AuditLogger;
use MG\Security\FeatureFlags;
use MG\Services\AuthService;

/**
 * AdminController - FEATURE_ADMIN_PANEL arkasında + admin.users/admin.audit permission.
 * Not: Route etiketi permission gerektirir; controller ayrıca feature flag kontrol eder
 * (double gate: hem bayrak hem rol).
 */
final class AdminController
{
    public function index(Request $req, array $params, Container $c): Response
    {
        /** @var FeatureFlags $flags */
        $flags = $c->get(FeatureFlags::class);
        if (!$flags->enabled(FeatureFlags::ADMIN_PANEL)) {
            throw new AppException('Yönetici paneli kapalı (FEATURE_ADMIN_PANEL=false).', 404);
        }
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null || !$user->isAdmin()) {
            throw AppException::forbiddenAdmin();
        }
        $logs = AuditLog::recent($c->get(\PDO::class), 50);
        return (new Response())->body(HomeController::render(__DIR__ . '/../../app/Views/admin.php', [
            'title' => 'Admin', 'logs' => $logs, 'user' => $user,
        ]));
    }

    /** Örnek admin aksiyonu: kullanıcıyı pasifleştir (audit'e yazar). */
    public function deactivateUser(Request $req, array $params, Container $c): Response
    {
        /** @var FeatureFlags $flags */
        $flags = $c->get(FeatureFlags::class);
        if (!$flags->enabled(FeatureFlags::ADMIN_PANEL)) {
            throw new AppException('Yönetici paneli kapalı.', 404);
        }
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $actor = $sg->currentUser();
        if ($actor === null || !$actor->hasPermission('admin.users')) {
            throw AppException::forbiddenAdmin();
        }
        $targetId = (int)($params['id'] ?? 0);
        if ($targetId === $actor->id) {
            throw new AppException('Kendi hesabınızı pasifleştiremezsiniz.', 422);
        }
        $stmt = $c->get(\PDO::class)->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role_id <> 1');
        $stmt->execute([$targetId]);   // admin hesabı kilitli: dikey privilege escalation önleme

        /** @var AuditLogger $audit */
        $audit = $c->get(AuditLogger::class);
        $audit->log($actor->id, 'admin_action', $req->ip, $req->userAgent, 'user:' . $targetId, 'success', ['action' => 'deactivate']);

        $_SESSION['_mg_flash'] = 'Kullanıcı pasifleştirildi.';
        return (new Response())->redirect('/admin');
    }
}
