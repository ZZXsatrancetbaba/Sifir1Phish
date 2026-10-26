<?php

declare(strict_types=1);

namespace MG\Tests\Security;

use MG\Security\OutputEncoder as E;
use PHPUnit\Framework\TestCase;

final class OutputEncoderTest extends TestCase
{
    public function testHtmlTagsAreEncoded(): void
    {
        $out = E::escHtml('<script>alert(1)</script>');
        self::assertStringNotContainsString('<script>', $out);
        self::assertStringContainsString('&lt;script&gt;', $out);
    }

    public function testQuotesCannotBreakAttribute(): void
    {
        $out = E::escAttr('" onmouseover="evil()');
        self::assertStringNotContainsString('"', $out);
        self::assertStringContainsString('&quot;', $out);
    }

    public function testJsEncodingBlocksScriptBreakout(): void
    {
        $out = E::escJs(['x' => '</script><script>bad()</script>']);
        self::assertStringNotContainsString('</script>', $out);   // JSON_HEX_TAG -> <\/script>
    }

    public function testInvalidUtf8IsSubstituted(): void
    {
        $out = E::escHtml("bad\xB1\xAFbyte");
        self::assertStringContainsString("\uFFFD", $out);          // ENT_SUBSTITUTE
    }

    public function testSafeLinkRejectsDangerousSchemes(): void
    {
        self::assertNull(E::safeLink('javascript:alert(1)'));
        self::assertNull(E::safeLink('data:text/html,<h1>x'));
        self::assertNotNull(E::safeLink('https://example.org/doc'));
    }
}
