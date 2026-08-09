<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Routing;

use JardisCore\App\Handler\Response\ResolveResponse;
use JardisCore\App\Handler\Routing\ResolveRouteHandler;
use JardisCore\App\Tests\Support\DomainResponse;
use JardisCore\App\Tests\Support\FixedResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ResolveRouteHandler (E4/E6): turns a Route's raw
 * `Closure|RequestHandlerInterface` handler into a PSR-15
 * RequestHandlerInterface, folding ResolveResponse's auto-mapping into the
 * Closure case.
 */
final class ResolveRouteHandlerTest extends TestCase
{
    private function resolveRouteHandler(): ResolveRouteHandler
    {
        $factory = new Psr17Factory();

        return new ResolveRouteHandler((new ResolveResponse($factory, $factory))->__invoke(...));
    }

    public function testRequestHandlerInterfaceIsReturnedUnchanged(): void
    {
        $factory = new Psr17Factory();
        $fixed = new FixedResponseHandler($factory->createResponse(204));
        $resolve = $this->resolveRouteHandler();

        $wrapped = $resolve($fixed);

        $this->assertSame($fixed, $wrapped);
    }

    public function testClosureReturningADomainResponseIsMappedThroughResolveResponse(): void
    {
        $factory = new Psr17Factory();
        $resolve = $this->resolveRouteHandler();

        $wrapped = $resolve(static fn () => new DomainResponse(status: 200, data: ['Sales' => ['ok' => true]]));
        $response = $wrapped->handle($factory->createServerRequest('GET', '/x'));

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(['Sales' => ['ok' => true]], $body['data']);
    }

    public function testClosureReturningAResponseInterfaceIsPassedThroughViaResolveResponse(): void
    {
        $factory = new Psr17Factory();
        $expected = $factory->createResponse(200);
        $resolve = $this->resolveRouteHandler();

        $wrapped = $resolve(static fn () => $expected);
        $response = $wrapped->handle($factory->createServerRequest('GET', '/x'));

        $this->assertSame($expected, $response);
    }
}
