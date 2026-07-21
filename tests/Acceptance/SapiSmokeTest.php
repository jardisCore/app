<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Acceptance;

use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * SAPI smoke test (P6 mandatory AK, E15): runs the App-Layer behind a real
 * `php -S` built-in server - IN this same phpcli container (self-contained:
 * both `php` and `curl` already live here, so no cross-container
 * networking is needed; a manually started dev server on a wrong/foreign
 * port is exactly the trap E15/L13 warns about, which the dynamic free
 * port below avoids) - and drives it with real HTTP requests via the
 * `curl` binary.
 *
 * Proves what an in-process PHPUnit call never can:
 * (a) the health route answers over a real HTTP connection;
 * (b) the Raw-Body-Invariante (PRD §5, P2 AK, B2 in the AK-Matrix) holds
 *     all the way through a real SAPI, not just in-process - a POST body
 *     with significant whitespace and Unicode content comes back
 *     byte-identical from an echo route;
 * (c) HEAD on a GET route suppresses the body and still carries the
 *     correct Content-Length (E13).
 *
 * E15 conventions: dynamic free port (found via a throwaway
 * `stream_socket_server`, released again before `php -S` binds it),
 * a readiness poll before any assertion, a try/finally teardown that
 * always terminates the server process, and `-d display_errors=Off` on
 * the server (production-like).
 */
final class SapiSmokeTest extends TestCase
{
    private const READINESS_ATTEMPTS = 50;
    private const READINESS_DELAY_MICROSECONDS = 100_000;

    /** @var resource|null */
    private $serverProcess;

    protected function setUp(): void
    {
        $this->serverProcess = null;
    }

    protected function tearDown(): void
    {
        if ($this->serverProcess !== null) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
            $this->serverProcess = null;
        }
    }

    public function testHealthRawBodyAndHeadOverARealSapiServer(): void
    {
        $port = $this->findFreePort();
        $baseUrl = sprintf('http://127.0.0.1:%d', $port);
        $fixture = dirname(__DIR__) . '/Support/Fixtures/sapi-smoke-index.php';
        $this->assertFileExists($fixture);

        $this->serverProcess = $this->startServer($port, $fixture);
        $this->waitUntilReady($baseUrl . '/health');

        $this->assertHealthRespondsOk($baseUrl);
        $this->assertRawBodyIsByteIdenticalThroughTheRealSapi($baseUrl);
        $this->assertHeadSuppressesBodyAndKeepsContentLength($baseUrl);
    }

    /**
     * @return resource
     */
    private function startServer(int $port, string $fixture)
    {
        $command = sprintf(
            '%s -d display_errors=Off -d error_reporting=E_ALL -S 127.0.0.1:%d %s',
            escapeshellarg(PHP_BINARY),
            $port,
            escapeshellarg($fixture),
        );

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($command, $descriptors, $pipes);

        if ($process === false) {
            throw new RuntimeException('Could not start the php -S SAPI smoke server.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return $process;
    }

    private function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new RuntimeException(sprintf('Could not find a free port: %s', $errstr));
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        if ($name === false) {
            throw new RuntimeException('Could not read the free port back from the throwaway socket.');
        }

        $port = (int) substr($name, (int) strrpos($name, ':') + 1);
        if ($port <= 0) {
            throw new RuntimeException('Could not determine a free port.');
        }

        return $port;
    }

    private function waitUntilReady(string $healthUrl): void
    {
        for ($attempt = 0; $attempt < self::READINESS_ATTEMPTS; $attempt++) {
            if ($this->curlStatus($healthUrl) === 200) {
                return;
            }

            usleep(self::READINESS_DELAY_MICROSECONDS);
        }

        throw new RuntimeException('SAPI smoke server did not become ready in time.');
    }

    private function curlStatus(string $url): ?int
    {
        $output = shell_exec(sprintf('curl -s -o /dev/null -w "%%{http_code}" %s', escapeshellarg($url)));

        return $output === null || $output === '' ? null : (int) $output;
    }

    private function assertHealthRespondsOk(string $baseUrl): void
    {
        $response = shell_exec(sprintf('curl -s %s', escapeshellarg($baseUrl . '/health')));

        $this->assertSame('{"status":200}', $response);
    }

    private function assertRawBodyIsByteIdenticalThroughTheRealSapi(string $baseUrl): void
    {
        $body = "  leading and trailing whitespace \t\n plus unicode: héllo wörld 🎉 \n\n";
        $bodyFile = tempnam(sys_get_temp_dir(), 'sapi_smoke_body_');
        if ($bodyFile === false) {
            throw new RuntimeException('Could not create a temp file for the raw-body fixture.');
        }

        try {
            file_put_contents($bodyFile, $body);
            $command = sprintf(
                'curl -s --data-binary %s -X POST %s',
                escapeshellarg('@' . $bodyFile),
                escapeshellarg($baseUrl . '/echo'),
            );
            $response = shell_exec($command);
        } finally {
            @unlink($bodyFile);
        }

        $this->assertSame($body, $response, 'The echoed body must be byte-identical to the raw POST body.');
    }

    private function assertHeadSuppressesBodyAndKeepsContentLength(string $baseUrl): void
    {
        $headers = (string) shell_exec(sprintf('curl -s -I -X HEAD %s', escapeshellarg($baseUrl . '/greet')));

        $this->assertMatchesRegularExpression('/^HTTP\/\d\.\d 200/', $headers);
        $this->assertMatchesRegularExpression('/Content-Length:\s*5/i', $headers);

        // shell_exec() returns null (not '') when the command produces
        // zero bytes of output - which is exactly the expected case here
        // (HEAD must yield no body at all).
        $body = shell_exec(sprintf('curl -s -X HEAD %s', escapeshellarg($baseUrl . '/greet')));

        $this->assertSame('', $body ?? '');
    }
}
