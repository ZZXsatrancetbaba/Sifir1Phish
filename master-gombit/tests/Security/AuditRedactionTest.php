<?php

declare(strict_types=1);

namespace MG\Tests\Security;

use MG\Core\Config;
use MG\Security\AuditLogger;
use PHPUnit\Framework\TestCase;

/**
 * AuditLogger redaksiyon testi DB'siz çalışır (statik metodlar).
 * Insert yolu Feature test'te sqlite ile doğrulanabilir.
 */
final class AuditRedactionTest extends TestCase
{
    public function testSensitiveKeysRedacted(): void
    {
        $meta = AuditLogger::redact([
            'password' => 'S3cret!',
            'csrf_token' => 'abc123',
            'flag' => 'MG{x}',
            'answer' => 'MG{x}',
            'nested' => ['Authorization' => 'Bearer t0ken'],
            'role' => 'student',
        ]);
        self::assertSame('[REDACTED]', $meta['password']);
        self::assertSame('[REDACTED]', $meta['csrf_token']);
        self::assertSame('[REDACTED]', $meta['flag']);
        self::assertSame('[REDACTED]', $meta['answer']);
        self::assertSame('[REDACTED]', $meta['nested']['Authorization']);
        self::assertSame('student', $meta['role']);   // zararsız alan korunur
    }

    public function testIpHashIsDeterministicAndIrreversible(): void
    {
        putenv('AUDIT_IP_PEPPER=test-pepper');
        Config::load(null);
        $h1 = AuditLogger::pepperedHash('127.0.0.1');
        $h2 = AuditLogger::pepperedHash('127.0.0.1');
        self::assertSame($h1, $h2);
        self::assertSame(64, strlen($h1));
        self::assertStringNotContainsString('127.0.0.1', $h1);
    }
}
