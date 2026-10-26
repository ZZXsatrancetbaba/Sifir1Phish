<?php

declare(strict_types=1);

namespace MG\Tests\Unit;

use MG\Core\Response;
use MG\Core\Router;
use MG\Exceptions\AppException;
use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    public function testRouterMatchesIntParamsOnly(): void
    {
        $r = new Router();
        $r->get('/labs/{id}', static fn() => 'ok');
        $hit = $r->match('GET', '/labs/42');
        self::assertSame(['id' => '42'], $hit['params']);

        // Non-int segment eşleşmez -> 404 (traversal/obfuscation denemeleri elenir)
        $this->expectException(AppException::class);
        $r->match('GET', '/labs/../../etc');
    }

    public function testOpenRedirectBlocked(): void
    {
        $resp = (new Response())->redirect('https://evil.example/x');
        $ref = new \ReflectionProperty(Response::class, 'headers');
        self::assertSame('/', $ref->getValue($resp)['Location']);   // mutlak dış adres /'e döner

        $ok = (new Response())->redirect('/dashboard');
        self::assertSame('/dashboard', $ref->getValue($ok)['Location']);
    }

    public function testJsonResponseHasCorrectType(): void
    {
        $resp = (new Response())->json(['a' => 1]);
        $ref = new \ReflectionProperty(Response::class, 'headers');
        self::assertStringContainsString('application/json', $ref->getValue($resp)['Content-Type']);
    }
}
