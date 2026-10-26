<?php

declare(strict_types=1);

namespace MG\Controllers;

use MG\Core\Container;
use MG\Core\Request;
use MG\Core\Response;
use MG\Exceptions\AppException;
use MG\Security\SessionGuard;
use MG\Services\LabService;

/**
 * UploadController - Lab 07 dosya yükleme girişi.
 * Tüm doğrulama LabService::storeUpload içinde (whitelist + magic byte + random name).
 */
final class UploadController
{
    public function upload(Request $req, array $params, Container $c): Response
    {
        /** @var SessionGuard $sg */
        $sg = $c->get(SessionGuard::class);
        $user = $sg->currentUser();
        if ($user === null) {
            throw new AppException('Giriş gerekli.', 401);
        }
        $file = $req->file('document');
        if ($file === null) {
            throw new AppException('Dosya bulunamadı.', 422);
        }
        /** @var LabService $labs */
        $labs = $c->get(LabService::class);
        $res = $labs->storeUpload($user, $file);
        $_SESSION['_mg_flash'] = 'Dosya yüklendi: #' . $res['id'];
        return (new Response())->redirect('/dashboard');
    }
}
