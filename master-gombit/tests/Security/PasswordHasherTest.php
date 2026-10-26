<?php

declare(strict_types=1);

namespace MG\Tests\Security;

use MG\Security\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    public function testHashVerifyRoundTrip(): void
    {
        $hash = PasswordHasher::hash('MgStudent2026!');
        self::assertTrue(PasswordHasher::verify('MgStudent2026!', $hash));
        self::assertFalse(PasswordHasher::verify('wrong-pass', $hash));
    }

    public function testModernAlgorithmPreferred(): void
    {
        $hash = PasswordHasher::hash('SomeGoodPass123!');
        if (defined('PASSWORD_ARGON2ID')) {
            self::assertStringStartsWith('$argon2id$', $hash);
        } else {
            self::assertStringStartsWith('$2y$', $hash);   // bcrypt fallback
        }
    }

    public function testStrengthPolicy(): void
    {
        self::assertFalse(PasswordHasher::isStrongEnough('short1!'));          // <10
        self::assertFalse(PasswordHasher::isStrongEnough('alllowercasechars')); // tek sınıf
        self::assertTrue(PasswordHasher::isStrongEnough('GoodPassw0rd!'));
        self::assertFalse(PasswordHasher::isStrongEnough(str_repeat('Aa1!', 60))); // >190 DoS koruması
    }

    public function testNeedsRehashForLegacyBcrypt(): void
    {
        if (!defined('PASSWORD_ARGON2ID')) {
            self::markTestSkipped('argon2id yok');
        }
        $bcrypt = password_hash('Xx12345678!', PASSWORD_BCRYPT, ['cost' => 12]);
        self::assertTrue(PasswordHasher::needsRehash($bcrypt));
        self::assertFalse(PasswordHasher::needsRehash(PasswordHasher::hash('Xx12345678!')));
    }
}
