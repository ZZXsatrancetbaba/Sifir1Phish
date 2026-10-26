<?php

declare(strict_types=1);

namespace MG\Controllers;

use MG\Core\Container;
use MG\Core\Request;
use MG\Core\Response;
use MG\Exceptions\AppException;
use MG\Models\LabModule;
use MG\Security\CsrfTokenManager;
use MG\Security\FeatureFlags;
use MG\Security\InputValidator;
use MG\Services\FlagService;
use MG\Services\LabService;

/**
 * LabController - lab listesi/detay/bayrak gönderimi/not ekleme.
 * Güvenlik akışı:
 *  - slug whitelist -> bySlug (feature flag sunucu tarafında filtrelenir)
 *  - POST /labs/{id}/submit -> CSRF (App middleware) + lab.submit permission (route etiketi)
 *  - FlagService rate-limit + hash_equals; ham bayrak asla geri vermez
 */
final class LabController
{
    public function show(Request $req, array $params, Container $c): Response
    {
        $slug = InputValidator::slug((string)($params['slug'] ?? ''));
        if ($slug === null) {
            throw new AppException('Geçersiz lab adresi.', 404);
        }
        /** @var FeatureFlags $flags */
        $flags = $c->get(FeatureFlags::class);
        $lab = LabModule::bySlug($c->get(\PDO::class), $slug, $flags);
        if ($lab === null) {
            throw new AppException('Lab bulunamadı veya şu anda kapalı.', 404);
        }

        /** @var CsrfTokenManager $csrf */
        $csrf = $c->get(CsrfTokenManager::class);
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();

        /** @var LabService $labs */
        $labs = $c->get(LabService::class);
        $notes = $user !== null ? $labs->listNotes($user, (int)$lab['id']) : [];

        // docs/LAB_GUIDE.md'deki modül dosyasını oku (README.md - statik eğitim içeriği)
        // Slug whitelist'ten geçmiş değer; realpath ile symlink/traversal kanıtı.
        $labsRoot = realpath(dirname(__DIR__, 2) . '/labs');
        $guideFile = $labsRoot !== false ? $labsRoot . '/' . $slug . '/README.md' : null;
        $resolved = $guideFile !== null && is_file($guideFile) ? realpath($guideFile) : false;
        $guide = ($resolved !== false && str_starts_with($resolved, (string)$labsRoot))
            ? (string)file_get_contents($resolved)
            : '';

        return (new Response())->body(HomeController::render(__DIR__ . '/../../app/Views/labs/show.php', [
            'title' => (string)$lab['title'],
            'lab' => $lab,
            'csrf' => $csrf->token(),
            'user' => $user,
            'notes' => $notes,
            'guide' => $guide,
            'insecureBanner' => $flags->enabled(FeatureFlags::INSECURE_DEMOS),
        ]));
    }

    public function submitFlag(Request $req, array $params, Container $c): Response
    {
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null) {
            throw new AppException('Önce giriş yapın.', 401);
        }
        $labId = (int)($params['id'] ?? 0);
        /** @var FeatureFlags $flags */
        $flags = $c->get(FeatureFlags::class);
        $stmt = $c->get(\PDO::class)->prepare(
            'SELECT id, slug, difficulty, requires_feature_flag FROM lab_modules WHERE id = ? AND is_enabled = 1 LIMIT 1'
        );
        $stmt->execute([$labId]);
        $lab = $stmt->fetch();
        if ($lab === false) {
            throw new AppException('Lab bulunamadı.', 404);
        }
        $flagName = $lab['requires_feature_flag'];
        if ($flagName !== null && !$flags->enabled(strtolower((string)$flagName))) {
            throw new AppException('Bu lab şu anda kapalı.', 403);
        }

        $answer = InputValidator::answer($req->str('answer'));
        if ($answer === null) {
            throw new AppException('Cevap biçimi geçersiz.', 422);
        }

        /** @var FlagService $svc */
        $svc = $c->get(FlagService::class);
        $ok = $svc->checkAndRecord($user->id, (array)$lab, $answer, $req->ip, $req->userAgent);

        $_SESSION['_mg_flash'] = $ok
            ? '✅ Bayrak doğru! Rehber bölümündeki düzeltme adımlarını okumayı unutma.'
            : '❌ Yanlış bayrak veya deneme limiti aşıldı.';
        return (new Response())->redirect('/labs/' . InputValidator::slug((string)$lab['slug']) );
    }

    public function addNote(Request $req, array $params, Container $c): Response
    {
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null) {
            throw new AppException('Önce giriş yapın.', 401);
        }
        $labId = (int)($params['id'] ?? 0);
        $content = InputValidator::noteContent($req->str('content'));
        if ($content === null || $content === '') {
            throw new AppException('Not içeriği geçersiz.', 422);
        }
        /** @var LabService $labs */
        $labs = $c->get(LabService::class);
        $labs->saveNote($user, $labId, $content);   // owner = oturum kullanıcısı (SQL seviyesinde)
        return (new Response())->redirect('/labs/' . rawurlencode((string)$this->slugOf($c, $labId)));
    }

    private function slugOf(Container $c, int $labId): string
    {
        $stmt = $c->get(\PDO::class)->prepare('SELECT slug FROM lab_modules WHERE id = ? LIMIT 1');
        $stmt->execute([$labId]);
        return (string)($stmt->fetchColumn() ?: '');
    }
}
