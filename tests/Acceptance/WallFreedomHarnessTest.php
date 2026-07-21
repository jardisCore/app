<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * B5 Wandfreiheits-Harness (PRD §1/§5, PLAN P7 Block C): proves the
 * vendored, generated Ecommerce fixture (`examples/basic/domain/Ecommerce/`)
 * carries no dependency on the App-Layer package — the dependency arrow
 * only ever points App-Layer -> Domain, never the reverse:
 *
 * (a) the vendored domain brings no own `composer.json` (no composer
 *     dependency it could declare `jardiscore/app` in);
 * (b) a namespace scan finds zero `JardisCore\App` references anywhere
 *     in the vendored domain's source.
 */
final class WallFreedomHarnessTest extends TestCase
{
    private const DOMAIN_PATH = __DIR__ . '/../../examples/basic/domain/Ecommerce';

    public function testVendoredDomainCarriesNoOwnComposerJson(): void
    {
        $this->assertDirectoryExists(self::DOMAIN_PATH);

        $composerFiles = $this->findFiles(self::DOMAIN_PATH, 'composer.json');

        $this->assertSame([], $composerFiles, 'The vendored domain must not carry its own composer.json.');
    }

    public function testVendoredDomainNeverImportsTheAppLayerNamespace(): void
    {
        $phpFiles = $this->findFiles(self::DOMAIN_PATH, '.php');
        $this->assertNotEmpty($phpFiles, 'Expected the vendored domain to contain PHP files.');

        $offenders = [];
        foreach ($phpFiles as $file) {
            $contents = (string) file_get_contents($file);
            if (str_contains($contents, 'JardisCore\\App')) {
                $offenders[] = $file;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'The vendored domain must never import the App-Layer namespace (JardisCore\\App).',
        );
    }

    /**
     * @return list<string>
     */
    private function findFiles(string $dir, string $suffix): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && str_ends_with($file->getFilename(), $suffix)) {
                $found[] = $file->getPathname();
            }
        }

        sort($found);

        return $found;
    }
}
