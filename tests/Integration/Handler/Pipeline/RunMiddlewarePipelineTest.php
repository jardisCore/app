<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Pipeline;

use JardisCore\App\Handler\Pipeline\RunMiddlewarePipeline;
use JardisCore\App\Tests\Support\CallLog;
use JardisCore\App\Tests\Support\OrderRecordingMiddleware;
use JardisCore\App\Tests\Support\RecordingRequestHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RunMiddlewarePipeline (E9): global middleware wraps
 * route-specific middleware (global = outer, route = inner); within each
 * group, execution order equals registration order - deterministic and
 * proven in both request and response direction.
 */
final class RunMiddlewarePipelineTest extends TestCase
{
    public function testGlobalWrapsRouteSpecificInDeterministicOrderBothDirections(): void
    {
        $factory = new Psr17Factory();
        $log = new CallLog();
        $global1 = new OrderRecordingMiddleware($log, 'G1');
        $global2 = new OrderRecordingMiddleware($log, 'G2');
        $route1 = new OrderRecordingMiddleware($log, 'R1');
        $route2 = new OrderRecordingMiddleware($log, 'R2');
        $finalHandler = new RecordingRequestHandler($log, 'final', $factory->createResponse(200));
        $pipeline = new RunMiddlewarePipeline();

        $response = $pipeline(
            [$global1, $global2],
            [$route1, $route2],
            $finalHandler,
            $factory->createServerRequest('GET', '/x'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'G1:request',
            'G2:request',
            'R1:request',
            'R2:request',
            'final',
            'R2:response',
            'R1:response',
            'G2:response',
            'G1:response',
        ], $log->entries);
    }

    public function testOrderingIsDeterministicAcrossRepeatedRuns(): void
    {
        $factory = new Psr17Factory();
        $log = new CallLog();
        $global = new OrderRecordingMiddleware($log, 'G');
        $route = new OrderRecordingMiddleware($log, 'R');
        $finalHandler = new RecordingRequestHandler($log, 'final', $factory->createResponse(200));
        $pipeline = new RunMiddlewarePipeline();
        $request = $factory->createServerRequest('GET', '/x');

        $pipeline([$global], [$route], $finalHandler, $request);
        $pipeline([$global], [$route], $finalHandler, $request);

        $this->assertSame([
            'G:request', 'R:request', 'final', 'R:response', 'G:response',
            'G:request', 'R:request', 'final', 'R:response', 'G:response',
        ], $log->entries);
    }

    public function testNoMiddlewareCallsFinalHandlerDirectly(): void
    {
        $factory = new Psr17Factory();
        $log = new CallLog();
        $finalHandler = new RecordingRequestHandler($log, 'final', $factory->createResponse(204));
        $pipeline = new RunMiddlewarePipeline();

        $response = $pipeline([], [], $finalHandler, $factory->createServerRequest('GET', '/x'));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(['final'], $log->entries);
    }

    public function testOnlyRouteMiddlewareWithoutGlobalStillWraps(): void
    {
        $factory = new Psr17Factory();
        $log = new CallLog();
        $route = new OrderRecordingMiddleware($log, 'R');
        $finalHandler = new RecordingRequestHandler($log, 'final', $factory->createResponse(200));
        $pipeline = new RunMiddlewarePipeline();

        $pipeline([], [$route], $finalHandler, $factory->createServerRequest('GET', '/x'));

        $this->assertSame(['R:request', 'final', 'R:response'], $log->entries);
    }

    public function testOnlyGlobalMiddlewareWithoutRouteStillWraps(): void
    {
        $factory = new Psr17Factory();
        $log = new CallLog();
        $global = new OrderRecordingMiddleware($log, 'G');
        $finalHandler = new RecordingRequestHandler($log, 'final', $factory->createResponse(200));
        $pipeline = new RunMiddlewarePipeline();

        $pipeline([$global], [], $finalHandler, $factory->createServerRequest('GET', '/x'));

        $this->assertSame(['G:request', 'final', 'G:response'], $log->entries);
    }
}
