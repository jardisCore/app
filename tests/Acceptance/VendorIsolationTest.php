<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * K5 Vendor-Isolation (PRD §5, PLAN P7 Block C): the App-Layer package's
 * own vendor tree carries no `laravel/*` or `symfony/*` package — pass/fail
 * eindeutig via `composer.lock`, `psr/*` explicitly allowed.
 */
final class VendorIsolationTest extends TestCase
{
    public function testComposerLockCarriesNoLaravelOrSymfonyPackage(): void
    {
        $lockPath = dirname(__DIR__, 2) . '/composer.lock';
        $this->assertFileExists($lockPath);

        /** @var array{packages?: list<array{name: string}>, 'packages-dev'?: list<array{name: string}>} $lock */
        $lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);

        $names = array_map(
            static fn(array $package): string => $package['name'],
            [...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])],
        );

        $offenders = array_values(array_filter(
            $names,
            static fn(string $name): bool => str_starts_with($name, 'laravel/') || str_starts_with($name, 'symfony/'),
        ));

        $this->assertSame(
            [],
            $offenders,
            'The App-Layer vendor tree must not contain a laravel/* or symfony/* package.',
        );
    }

    public function testVendorDirectoryCarriesNoLaravelOrSymfonyPackageDirectory(): void
    {
        $vendorPath = dirname(__DIR__, 2) . '/vendor';
        $this->assertDirectoryExists($vendorPath);

        foreach (['laravel', 'symfony'] as $vendor) {
            $this->assertDirectoryDoesNotExist(
                $vendorPath . '/' . $vendor,
                sprintf('vendor/%s must not exist in the App-Layer vendor tree.', $vendor),
            );
        }
    }
}
