<?php

declare(strict_types=1);

namespace MG\Tests\Security;

use MG\Security\FeatureFlags;
use PHPUnit\Framework\TestCase;

final class FeatureFlagsTest extends TestCase
{
    public function testUnknownFlagIsFalseFailClosed(): void
    {
        self::assertFalse((new FeatureFlags())->enabled('feature_evil_flag'));
    }

    public function testInsecureDemosRespectEnvAndLocalBind(): void
    {
        putenv('FEATURE_INSECURE_DEMOS=false');
        self::assertFalse((new FeatureFlags())->enabled(FeatureFlags::INSECURE_DEMOS));

        putenv('FEATURE_INSECURE_DEMOS=true');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';   // lokal bind => açılabilir
        self::assertTrue((new FeatureFlags())->enabled(FeatureFlags::INSECURE_DEMOS));

        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';     // harici => REDDEDİLİR (çift kilit)
        self::assertFalse((new FeatureFlags())->enabled(FeatureFlags::INSECURE_DEMOS));
        unset($_SERVER['REMOTE_ADDR']);
        putenv('FEATURE_INSECURE_DEMOS=false');
    }
}
