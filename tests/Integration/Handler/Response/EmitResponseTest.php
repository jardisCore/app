<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Response;

use JardisCore\App\Handler\Response\EmitResponse;
use JardisCore\App\Tests\Support\ErrorLogRedirect;
use JardisCore\App\Tests\Support\HeaderCallLog;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for EmitResponse (E8/E13, the fourth F9 error timepoint).
 *
 * Testability (documented decision, see the class docblock): CLI SAPI's
 * `header()` is a silent no-op with nothing observable via `headers_list()`
 * (verified against this project's actual phpcli container), so the
 * header-emitting call and the `headers_sent()` query are both injected
 * here as recording/fixed fakes instead of the real PHP functions - real,
 * over-the-wire header transmission is proven only by the SAPI smoke test.
 * Body output goes through the real `echo`, captured via output buffering.
 */
final class EmitResponseTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function emitterWithHeaderSpy(HeaderCallLog $log, bool $headersSent = false): EmitResponse
    {
        return new EmitResponse(
            static function (string $line, bool $replace) use ($log): void {
                $log->record($line, $replace);
            },
            static fn (): bool => $headersSent,
        );
    }

    public function testSendsStatusLineAndHeadersThenTheBody(): void
    {
        $log = new HeaderCallLog();
        $emit = $this->emitterWithHeaderSpy($log);
        $request = $this->factory->createServerRequest('GET', '/x');
        $response = $this->factory->createResponse(201)->withHeader('X-Test', 'value');
        $response->getBody()->write('hello');

        ob_start();
        $emit($request, $response);
        $output = ob_get_clean();

        $this->assertSame('hello', $output);
        $this->assertSame(['HTTP/1.1 201 Created', true], $log->calls[0]);
        $this->assertContains(['X-Test: value', true], $log->calls);
        $this->assertContains(['Content-Length: 5', true], $log->calls);
    }

    public function testMultipleValuesForTheSameHeaderNameAreSentWithoutReplacingEachOther(): void
    {
        $log = new HeaderCallLog();
        $emit = $this->emitterWithHeaderSpy($log);
        $request = $this->factory->createServerRequest('GET', '/x');
        $response = $this->factory->createResponse(200)
            ->withAddedHeader('Set-Cookie', 'a=1')
            ->withAddedHeader('Set-Cookie', 'b=2');

        ob_start();
        $emit($request, $response);
        ob_end_clean();

        $this->assertContains(['Set-Cookie: a=1', true], $log->calls);
        $this->assertContains(['Set-Cookie: b=2', false], $log->calls);
    }

    public function testDoesNotOverwriteAnExistingContentLengthHeader(): void
    {
        $log = new HeaderCallLog();
        $emit = $this->emitterWithHeaderSpy($log);
        $request = $this->factory->createServerRequest('GET', '/x');
        $response = $this->factory->createResponse(200)->withHeader('Content-Length', '999');
        $response->getBody()->write('hello');

        ob_start();
        $emit($request, $response);
        ob_end_clean();

        $this->assertContains(['Content-Length: 999', true], $log->calls);
    }

    public function testHeadRequestSuppressesTheBodyButStillSendsContentLength(): void
    {
        $log = new HeaderCallLog();
        $emit = $this->emitterWithHeaderSpy($log);
        $request = $this->factory->createServerRequest('HEAD', '/x');
        $response = $this->factory->createResponse(200);
        $response->getBody()->write('hello');

        ob_start();
        $emit($request, $response);
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertContains(['Content-Length: 5', true], $log->calls);
    }

    public function testHeadersSentGuardSkipsTheStatusLineAndHeaderPhaseButStillSendsTheBody(): void
    {
        $log = new HeaderCallLog();
        $emit = $this->emitterWithHeaderSpy($log, headersSent: true);
        $request = $this->factory->createServerRequest('GET', '/x');
        $response = $this->factory->createResponse(200);
        $response->getBody()->write('hello');

        ob_start();
        $emit($request, $response);
        $output = ob_get_clean();

        $this->assertSame([], $log->calls);
        $this->assertSame('hello', $output);
    }

    public function testAnExceptionEscapingTheEmitIsCaughtAndLoggedWithABestEffortFiveHundred(): void
    {
        $errorLog = new ErrorLogRedirect();
        $errorLog->start();

        try {
            $log = new HeaderCallLog();
            $emit = $this->emitterWithHeaderSpy($log);
            $request = $this->factory->createServerRequest('GET', '/x');
            // @phpstan-ignore-next-line class.extendsFinalByPhpDoc (test double only, never shipped)
            $response = new class extends \Nyholm\Psr7\Response {
                public function getBody(): \Psr\Http\Message\StreamInterface
                {
                    throw new RuntimeException('body access exploded');
                }
            };

            $emit($request, $response);

            $this->assertSame(['HTTP/1.1 500 Internal Server Error', true], $log->calls[0]);
            $this->assertStringContainsString('body access exploded', $errorLog->contents());
        } finally {
            $errorLog->stop();
        }
    }
}
