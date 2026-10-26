<?php

declare(strict_types=1);

namespace MG\Controllers;

use MG\Core\Container;
use MG\Core\Request;
use MG\Core\Response;
use MG\Exceptions\AuthException;
use MG\Exceptions\ValidationException;
use MG\Security\CsrfTokenManager;
use MG\Security\InputValidator;
use MG\Services\AuthService;

/** AuthController - login/logout/register. Parola POST body'de kalır, GET'e asla taşınmaz. */
final class AuthController
{
    public function showLogin(Request $req, array $params, Container $c): Response
    {
        /** @var CsrfTokenManager $csrf */
        $csrf = $c->get(CsrfTokenManager::class);
        return (new Response())->body(HomeController::render(__DIR__ . '/../../app/Views/auth/login.php', [
            'title' => 'Giriş', 'csrf' => $csrf->token(), 'flash' => '',
        ]));
    }

    public function login(Request $req, array $params, Container $c): Response
    {
        $username = InputValidator::username($req->str('username'));
        $password = $req->str('password');
        if ($username === null || $password === '') {
            throw new ValidationException(['username' => 'Kullanıcı adı geçersiz veya boş.']);
        }

        /** @var AuthService $auth */
        $auth = $c->get(AuthService::class);
        $user = $auth->login($username, $password, $req->ip, $req->userAgent);

        // Login sonrası flash + dashboard'a yönlendir (POST-Redirect-GET: PRG)
        $_SESSION['_mg_flash'] = 'Hoş geldin, ' . $user->username;
        return (new Response())->redirect('/dashboard');
    }

    public function logout(Request $req, array $params, Container $c): Response
    {
        /** @var AuthService $auth */
        $auth = $c->get(AuthService::class);
        /** @var \MG\Security\SessionGuard $sg */
        $sg = $c->get(\MG\Security\SessionGuard::class);
        $auth->logout($sg->userId(), $req->ip, $req->userAgent);
        return (new Response())->redirect('/');
    }

    public function register(Request $req, array $params, Container $c): Response
    {
        $username = InputValidator::username($req->str('username'));
        $email = $req->str('email') !== '' ? InputValidator::email($req->str('email')) : null;
        $password = $req->str('password');
        if ($username === null) {
            throw new ValidationException(['username' => 'Kullanıcı adı 3-64 karakter [a-z0-9._-] olmalı.']);
        }
        if ($req->str('email') !== '' && $email === null) {
            throw new ValidationException(['email' => 'E-posta geçersiz.']);
        }
        /** @var AuthService $auth */
        $auth = $c->get(AuthService::class);
        $auth->register($username, $email, $password);
        $_SESSION['_mg_flash'] = 'Kayıt tamamlandı. Giriş yapabilirsiniz.';
        return (new Response())->redirect('/login');
    }
}
