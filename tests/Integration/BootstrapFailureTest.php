<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Proves the "Bootstrap-Fehlschlag" F9 case (PRD §3.F9/P6 AK): when the
 * ENV-Packer throws before any pipeline exists to catch it, the client
 * still gets nothing but a bare failure (no message/trace/path leaks to
 * stdout) while the real cause is findable in the server/container log
 * (stderr here) - exactly the contract `docs/getting-started.md`'s
 * `public/index.php` recipe relies on via `display_errors=Off` plus its
 * documented shutdown-function safety net.
 *
 * Runs the fixture as a real PHP sub-process (E15) rather than in-process:
 * the failure happens before any App-Layer object exists to assert
 * against, only the process's own stdout/stderr/exit code are observable.
 *
 * Environment note: this container's generated php.ini
 * (support/docker-compose.yml phpcli image, `99-runtime-config.ini`) sets
 * `error_reporting="E_ALL & ~E_DEPRECATED & ~E_STRICT"` WITH quotes, which
 * PHP's ini parser treats as a literal string rather than a bitwise
 * constant expression and casts to `0` - silently suppressing ALL error
 * output for every PHP process in this container (a pre-existing
 * infrastructure quirk of the shared phpcli image, unrelated to this
 * package). The sub-process below passes an explicit
 * `-d error_reporting=E_ALL` override to work around it, so the fatal
 * error actually reaches stderr for this test to assert on.
 */
final class BootstrapFailureTest extends TestCase
{
    public function testBootstrapFailureYieldsNoClientOutputAndLogsTheCauseToStderr(): void
    {
        $fixture = dirname(__DIR__) . '/Support/Fixtures/bootstrap-fail-index.php';
        $this->assertFileExists($fixture);

        $command = sprintf(
            '%s -d error_reporting=E_ALL -d display_errors=Off -d log_errors=On %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($fixture),
        );

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($command, $descriptors, $pipes);

        if ($process === false) {
            throw new RuntimeException('Could not start the bootstrap-failure fixture sub-process.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertNotSame(0, $exitCode, 'A bootstrap failure must not exit successfully.');
        $this->assertSame('', trim((string) $stdout), 'No client-facing output must escape a bootstrap failure.');
        // jardiscore/kernel v2.0.0 (env-konfiguration, R2) always mkdir()s
        // "<projectRoot>/config/env" (G1) before ever reaching DomainKernel's
        // constructor - the fixture now trips the packer's own RuntimeException
        // instead of DomainKernel's "projectRoot must not be empty" guard (see
        // the fixture's comment for why this is still deterministic). Same
        // failure mode (Fehlerfall bleibt Fehlerfall): a throw before any
        // pipeline exists, nothing leaks to the client, cause lands in stderr.
        $this->assertStringContainsString('RuntimeException', (string) $stderr);
        $this->assertStringContainsString('Failed to create config directory', (string) $stderr);
    }
}
