<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Config;

use JardisCore\App\Config\AppConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AppConfig - a readonly VO with injected values only (E11).
 */
final class AppConfigTest extends TestCase
{
    public function testDefaultsToDebugDisabled(): void
    {
        $config = new AppConfig();

        $this->assertFalse($config->debug);
    }

    public function testCarriesInjectedDebugValue(): void
    {
        $config = new AppConfig(debug: true);

        $this->assertTrue($config->debug);
    }
}
