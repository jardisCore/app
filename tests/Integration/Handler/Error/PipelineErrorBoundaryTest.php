<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Error;

use JardisCore\App\Config\AppConfig;
use JardisCore\App\Handler\Error\HandleThrowable;
use JardisCore\App\Handler\Pipeline\RunMiddlewarePipeline;
use JardisCore\App\Tests\Support\ErrorLogRedirect;
use JardisCore\App\Tests\Support\FixedResponseHandler;
use JardisCore\App\Tests\Support\LoggerSpy;
use JardisCore\App\Tests\Support\ThrowingMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Proves HandleThrowable is genuinely the outermost pipeline layer (E8):
 * an exception thrown from INSIDE a middleware further down the chain -
 * not just from the final route handler - is still caught and mapped to a
 * generic 500, with the full error logged.
 */
final class PipelineErrorBoundaryTest extends TestCase
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

    public function testExceptionFromInnerMiddlewareIsCaughtByOutermostErrorLayer(): void
    {
        $factory = new Psr17Factory();
        $logger = new LoggerSpy();
        $handleThrowable = new HandleThrowable($factory, $factory, $logger, new AppConfig());
        $runPipeline = new RunMiddlewarePipeline();
        $throwingMiddleware = new ThrowingMiddleware('boom from deep inside the pipeline');
        $finalHandler = new FixedResponseHandler($factory->createResponse(200));
        $request = $factory->createServerRequest('GET', '/x');

        $response = $handleThrowable(
            static fn (ServerRequestInterface $req) => $runPipeline([], [$throwingMiddleware], $finalHandler, $req),
            $request,
        );

        $this->assertSame(500, $response->getStatusCode());
        $json = (string) $response->getBody();
        $this->assertStringNotContainsString('boom from deep inside the pipeline', $json);
        $this->assertCount(1, $logger->records);
        $this->assertSame('boom from deep inside the pipeline', $logger->records[0]['message']);
    }

    public function testExceptionFromGlobalMiddlewareIsAlsoCaught(): void
    {
        $factory = new Psr17Factory();
        $handleThrowable = new HandleThrowable($factory, $factory, null, new AppConfig());
        $runPipeline = new RunMiddlewarePipeline();
        $throwingMiddleware = new ThrowingMiddleware('boom from global middleware');
        $finalHandler = new FixedResponseHandler($factory->createResponse(200));
        $request = $factory->createServerRequest('GET', '/x');

        $response = $handleThrowable(
            static fn (ServerRequestInterface $req) => $runPipeline([$throwingMiddleware], [], $finalHandler, $req),
            $request,
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(
            '{"status":500,"data":{},"errors":{},"meta":{}}',
            (string) $response->getBody(),
        );
    }
}
