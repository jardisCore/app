<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * F6-Architektur-Regel + Negativ-Beweis (PRD §5, PLAN P7 Block C):
 * `ExampleApp\PhpStan\ForbidDomainInternalsRule` statically enforces that
 * route-handler code in `examples/basic/app/` may only reach the generated
 * domain through its legitimate outer door (BC facade, read facade, process
 * facade + DTOs) — never the aggregate write facade, a Command/Query
 * Handler, a Repository, or a Rule class directly.
 *
 * This test drives PHPStan itself as a subprocess against both the real
 * example code (must exit clean) and the negative fixture demonstrating
 * the exact bypass the rule exists to catch (must exit non-zero, with the
 * rule's own error identifier in the output) — `make qa` itself stays
 * green throughout, since the negative fixture lives outside the main
 * `phpstan.neon`/`phpcs.xml` scan (`src`/`tests` only) and outside any
 * Composer `autoload-dev` PSR-4 prefix.
 */
final class ArchitectureRuleHarnessTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../..';

    public function testTheRealExampleHandlerCodePassesTheF6ArchitectureRule(): void
    {
        [$exitCode, $output] = $this->runPhpstan('examples/basic/phpstan-handlers.neon');

        $this->assertSame(0, $exitCode, "phpstan-handlers.neon must exit clean:\n{$output}");
        $this->assertStringContainsString('[OK] No errors', $output);
    }

    public function testTheNegativeFixtureBypassingTheAggregateWriteFacadeIsCaught(): void
    {
        [$exitCode, $output] = $this->runPhpstan('examples/basic/phpstan-negative-fixture.neon');

        $this->assertNotSame(0, $exitCode, "phpstan-negative-fixture.neon was expected to fail:\n{$output}");
        $this->assertStringContainsString('jardis.f6OuterDoorOnly', $output);
        $this->assertStringContainsString('reaches past the outer door', $output);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function runPhpstan(string $configPath): array
    {
        $command = sprintf(
            '%s %s analyse -c %s --no-progress 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(self::PROJECT_ROOT . '/vendor/bin/phpstan'),
            escapeshellarg(self::PROJECT_ROOT . '/' . $configPath),
        );

        exec($command, $outputLines, $exitCode);

        return [$exitCode, implode("\n", $outputLines)];
    }
}
