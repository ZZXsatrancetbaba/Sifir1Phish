<?php

declare(strict_types=1);

namespace MG\Tests\Security;

use MG\Core\Request;
use MG\Exceptions\AppException;
use MG\Security\CsrfTokenManager;
use MG\Security\SessionGuard;
use PHPUnit\Framework\TestCase;

/**
 * CSRF testi: gerçek session yerine $_SESSION süperglobal'ini elle doldurup
 * validateOrFail davranışını doğrular (unit seviye).
 */
final class CsrfTokenManagerTest extends TestCase
{
    private function manager(): CsrfTokenManager
    {
        $guard = $this->createStub(SessionGuard::class);
        return new CsrfTokenManager($guard);
    }

    public function testTokenIsRandomAndStableInSession(): void
    {
        $_SESSION = [];
        $m = $this->manager();
        $t1 = $m->token();
        $t2 = $m->token();
        self::assertSame($t1, $t2);
        self::assertSame(64, strlen($t1));               // 32 bayt hex
        self::assertNotSame(bin2hex(str_repeat('A', 32)), $t1); // deterministik değil
    }

    public function testMissingTokenRejected(): void
    {
        $_SESSION = ['_mg_csrf' => str_repeat('a', 64)];
        $req = new Request('POST', '/x', [], [], [], [], '127.0.0.1', 'test');
        $this->expectException(AppException::class);
        $this->manager()->validateOrFail($req);
    }

    public function testWrongTokenRejectedRightAccepted(): void
    {
        $_SESSION = ['_mg_csrf' => str_repeat('a', 64)];
        $bad = new Request('POST', '/x', [], ['csrf_token' => str_repeat('b', 64)], [], [], '127.0.0.1', 'ua');
        $good = new Request('POST', '/x', [], ['csrf_token' => str_repeat('a', 64)], [], [], '127.0.0.1', 'ua');
        try { $this->manager()->validateOrFail($bad); self::fail('yanlış token kabul edildi'); }
        catch (AppException) { /* beklenen */ }
        $this->manager()->validateOrFail($good);          // exception fırlatmaz
        $hdr = new Request('POST', '/x', [], [], [], ['x-csrf-token' => str_repeat('a', 64)], '127.0.0.1', 'ua');
        $this->manager()->validateOrFail($hdr);           // header yolu da geçerli
    }

    public function testRotationInvalidatesOldToken(): void
    {
        $_SESSION = [];
        $m = $this->manager();
        $old = $m->token();
        $m->rotate();
        $new = $m->token();
        self::assertNotSame($old, $new);
        $req = new Request('POST', '/x', [], ['csrf_token' => $old], [], [], '127.0.0.1', 'ua');
        $this->expectException(AppException::class);
        $m->validateOrFail($req);
    }
}
