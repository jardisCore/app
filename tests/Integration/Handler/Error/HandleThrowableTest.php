<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Error;

use JardisCore\App\Config\AppConfig;
use JardisCore\App\Exception\InvalidJsonBody;
use JardisCore\App\Handler\Error\HandleThrowable;
use JardisCore\App\Tests\Support\ErrorLogRedirect;
use JardisCore\App\Tests\Support\LoggerSpy;
use JardisCore\App\Tests\Support\ThrowingLogger;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for HandleThrowable (E8/F9): the outermost pipeline layer. Covers
 * the InvalidJsonBody-400 case, the generic-500 case (negative assertions:
 * no message/class/trace leak; also for an exception shaped like the retired denial marker), logging at `error` level, the
 * `error_log` fallback (missing logger and a throwing logger), and the
 * APP_DEBUG detail switch.
 */
final class HandleThrowableTest extends TestCase
{
    private ErrorLogRedirect $errorLog;

    protected function setUp(): void
    {
        $this->errorLog = new ErrorLogRedirect();
        $this->errorLog->start();
    }

    protected function tearDown(): void
    {
        $this->errorLog->stop();
    }

    private function factory(): Psr17Factory
    {
        return new Psr17Factory();
    }

    public function testInnerResponseIsPassedThroughUnchangedWhenNoExceptionEscapes(): void
    {
        $factory = $this->factory();
        $expected = $factory->createResponse(200);
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());

        $response = $handle(static fn () => $expected, $factory->createServerRequest('GET', '/x'));

        $this->assertSame($expected, $response);
    }

    public function testInvalidJsonBodyMapsToCanonical400Envelope(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());

        $response = $handle(static function () {
            throw new InvalidJsonBody('Request body is not valid JSON.');
        }, $factory->createServerRequest('POST', '/x'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(400, $body['status']);
        $this->assertSame([], $body['data']);
        $this->assertSame('Request body is not valid JSON.', $body['errors']['message']);
        $this->assertSame([], $body['meta']);
    }

    public function testInvalidJsonBody400EnvelopeCoercesEmptyFieldsToJsonObjects(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());

        $response = $handle(static function () {
            throw new InvalidJsonBody('bad json');
        }, $factory->createServerRequest('POST', '/x'));

        $json = (string) $response->getBody();
        $this->assertStringContainsString('"data":{}', $json);
        $this->assertStringContainsString('"meta":{}', $json);
    }

    public function testExceptionWithMessageKeyAndContextButNoKnownTypeAnswersGeneric500(): void
    {
        $factory = $this->factory();
        $logger = new LoggerSpy();
        $handle = new HandleThrowable($factory, $factory, $logger, new AppConfig());

        $response = $handle(static function () {
            throw self::denial('actor.rightMissing', ['right' => 'sales.process:voucherRedeemCheck']);
        }, $factory->createServerRequest('POST', '/sales/voucherRedeemCheck'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('{"status":500,"data":{},"errors":{},"meta":{}}', (string) $response->getBody());
        $this->assertCount(1, $logger->records, 'an unknown exception is a failure: it is logged');
        $this->assertSame('error', $logger->records[0]['level']);
    }

    public function testFormerlyMarkedDenialLeaksNoMessageNoKeyNoContextEvenInProduction(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());

        $response = $handle(static function () {
            throw self::denial('actor.kindNotAllowed', ['kind' => 'anonymous'], 'secret internal message');
        }, $factory->createServerRequest('GET', '/x'));

        $json = (string) $response->getBody();
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('{"status":500,"data":{},"errors":{},"meta":{}}', $json);
        $this->assertStringNotContainsString('secret internal message', $json);
        $this->assertStringNotContainsString('actor.kindNotAllowed', $json);
        $this->assertStringNotContainsString('forbidden', $json);
    }

    /**
     * An exception shaped like the retired access-denied marker (message key and context), WITHOUT the marker:
     * the denial of a right is answered by the route (RequireRight), no exception type is answered 403 anymore.
     *
     * @param array<string, mixed> $context
     */
    private static function denial(string $key, array $context, string $message = 'denied'): \Throwable
    {
        return new class ($key, $context, $message) extends \RuntimeException {
            /**
             * @param array<string, mixed> $context
             */
            public function __construct(private readonly string $key, private readonly array $context, string $message)
            {
                parent::__construct($message);
            }

            public function getMessageKey(): string
            {
                return $this->key;
            }

            /**
             * @return array<string, mixed>
             */
            public function getContext(): array
            {
                return $this->context;
            }
        };
    }

    public function testGenericThrowableMapsTo500WithGenericBodyByDefault(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());

        $response = $handle(static function () {
            throw new RuntimeException('super secret internals leaked');
        }, $factory->createServerRequest('GET', '/x'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $json = (string) $response->getBody();
        $this->assertStringNotContainsString('super secret internals leaked', $json);
        $this->assertStringNotContainsString(RuntimeException::class, $json);
        $this->assertStringNotContainsString('.php', $json);
        $this->assertSame('{"status":500,"data":{},"errors":{},"meta":{}}', $json);
    }

    public function testGenericThrowableIsLoggedAtErrorLevelWithFullDetail(): void
    {
        $factory = $this->factory();
        $logger = new LoggerSpy();
        $handle = new HandleThrowable($factory, $factory, $logger, new AppConfig());
        $exception = new RuntimeException('boom');

        $handle(static function () use ($exception) {
            throw $exception;
        }, $factory->createServerRequest('GET', '/x'));

        $this->assertCount(1, $logger->records);
        $this->assertSame('error', $logger->records[0]['level']);
        $this->assertSame('boom', $logger->records[0]['message']);
        $this->assertSame($exception, $logger->records[0]['context']['exception']);
    }

    public function testLoggerThrowingStillReturns500AndFallsBackToErrorLog(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, new ThrowingLogger(), new AppConfig());

        $response = $handle(static function () {
            throw new RuntimeException('original failure');
        }, $factory->createServerRequest('GET', '/x'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('original failure', $this->errorLog->contents());
    }

    public function testWithoutLoggerFallsBackToErrorLogAndStillReturns500(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());

        $response = $handle(static function () {
            throw new RuntimeException('no logger configured');
        }, $factory->createServerRequest('GET', '/x'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('no logger configured', $this->errorLog->contents());
    }

    public function testDebugTrueIncludesExceptionDetailsInBody(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig(debug: true));

        $response = $handle(static function () {
            throw new RuntimeException('visible in debug mode');
        }, $factory->createServerRequest('GET', '/x'));

        $json = (string) $response->getBody();
        $this->assertStringContainsString('visible in debug mode', $json);
        $this->assertStringContainsString(RuntimeException::class, $json);
    }

    public function testDebugFalseByDefaultExcludesExceptionDetails(): void
    {
        $factory = $this->factory();
        $handle = new HandleThrowable($factory, $factory, null, new AppConfig());

        $response = $handle(static function () {
            throw new RuntimeException('must stay hidden');
        }, $factory->createServerRequest('GET', '/x'));

        $json = (string) $response->getBody();
        $this->assertStringNotContainsString('must stay hidden', $json);
    }
}
