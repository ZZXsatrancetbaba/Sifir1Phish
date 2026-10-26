<?php

declare(strict_types=1);

namespace MG\Tests\Security;

use MG\Security\InputValidator;
use PHPUnit\Framework\TestCase;

final class InputValidatorTest extends TestCase
{
    public function testUsernameWhitelist(): void
    {
        self::assertSame('a.b_c-1', InputValidator::username(' A.B_c-1 '));
        self::assertNull(InputValidator::username('ab'));                 // kısa
        self::assertNull(InputValidator::username('kullanıcı adı'));       // boşluk/unicode
        self::assertNull(InputValidator::username(str_repeat('a', 65)));  // uzun
    }

    public function testEmailValidation(): void
    {
        self::assertSame('x@y.local', InputValidator::email('X@Y.local'));
        self::assertNull(InputValidator::email('not-an-email'));
        self::assertNull(InputValidator::email(str_repeat('a', 250) . '@b.test'));
    }

    public function testAnswerVisibleAsciiOnly(): void
    {
        self::assertSame('MG{test}', InputValidator::answer(' MG{test} '));
        self::assertNull(InputValidator::answer("MG{\ntest}"));            // kontrol karakteri
        self::assertNull(InputValidator::answer(str_repeat('A', 129)));
    }

    public function testNoteStripsControlCharsAndLimitsLength(): void
    {
        self::assertSame('abc', InputValidator::noteContent("a\x00b\x1Bc"));
        self::assertNull(InputValidator::noteContent(str_repeat('n', 10001)));
    }

    public function testSlugAndOneOf(): void
    {
        self::assertSame('sql-awareness', InputValidator::slug('sql-awareness'));
        self::assertNull(InputValidator::slug('../../etc/passwd'));
        self::assertSame('score', InputValidator::oneOf('score', ['score','created_at']));
        self::assertNull(InputValidator::oneOf('password_hash', ['score','created_at']));
    }
}
