<?php

declare(strict_types=1);

use MG\Controllers\AdminController;
use MG\Controllers\AuthController;
use MG\Controllers\DashboardController;
use MG\Controllers\HomeController;
use MG\Controllers\LabController;
use MG\Controllers\UploadController;
use MG\Core\App;
use MG\Core\Router;
use MG\Models\Permission as P;

/**
 * Rota tanımları - route etiketli RBAC.
 * Her POST state-changing'dir; App middleware CSRF'yi otomatik zorlar.
 */
return static function (Router $r, App $app): void {
    $c = $app->container();

    $h = static fn(callable $fn) => static function ($req, $params) use ($fn, $c) {
        return $fn($req, $params, $c);
    };

    // Public
    $r->get('/', $h([HomeController::class, 'index']));
    $r->get('/login', $h([AuthController::class, 'showLogin']));
    $r->post('/login', $h([AuthController::class, 'login']));                 // + rate limit
    $r->post('/register', $h([AuthController::class, 'register']));
    $r->post('/logout', $h([AuthController::class, 'logout']), P::LAB_VIEW);   // logout da CSRF'li POST

    // Labs (auth required)
    $r->get('/labs/{slug}', $h([LabController::class, 'show']), P::LAB_VIEW);
    $r->post('/labs/{id}/submit', $h([LabController::class, 'submitFlag']), P::LAB_SUBMIT);
    $r->post('/labs/{id}/notes', $h([LabController::class, 'addNote']), P::NOTE_OWN);

    // Dashboard (auth required)
    $r->get('/dashboard', $h([DashboardController::class, 'index']), P::LAB_VIEW);
    $r->get('/report.json', $h([DashboardController::class, 'reportJson']), P::REPORT_EXPORT);
    $r->get('/report.csv', $h([DashboardController::class, 'reportCsv']), P::REPORT_EXPORT);
    $r->get('/files/{id}/download', $h([DashboardController::class, 'downloadFile']), P::FILE_DOWNLOAD_OWN);

    // Upload (POST multipart) - Lab 07
    $r->post('/labs/{id}/upload', $h([UploadController::class, 'upload']), P::FILE_UPLOAD);

    // Admin (feature flag + permission çift kapı)
    $r->get('/admin', $h([AdminController::class, 'index']), P::ADMIN_AUDIT);
    $r->post('/admin/users/{id}/deactivate', $h([AdminController::class, 'deactivateUser']), P::ADMIN_USERS);
};
