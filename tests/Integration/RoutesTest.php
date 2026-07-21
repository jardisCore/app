<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration;

use Closure;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\FixedResponseHandler;
use JardisCore\App\Tests\Support\InvokableEchoHandler;
use JardisCore\App\Tests\Support\NoopMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Routes registration collector (E5): verb registration,
 * immediate callable-to-Closure conversion (E4) for every callable shape,
 * global middleware collection, and the internal health() route (E10).
 */
final class RoutesTest extends TestCase
{
    private function createRoutes(): Routes
    {
        return new Routes(new Psr17Factory());
    }

    public function testGetPostPutPatchDeleteRegisterRoutesWithMethodAndPath(): void
    {
        $routes = $this->createRoutes();

        $routes->get('/orders/{id}', static fn () => null);
        $routes->post('/orders', static fn () => null);
        $routes->put('/orders/{id}', static fn () => null);
        $routes->patch('/orders/{id}', static fn () => null);
        $routes->delete('/orders/{id}', static fn () => null);

        $registered = $routes->routes();

        $this->assertCount(5, $registered);
        $this->assertSame(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], array_map(
            static fn ($route) => $route->method,
            $registered,
        ));
        $this->assertSame('/orders/{id}', $registered[0]->path);
    }

    public function testStringCallableIsConvertedToClosureImmediately(): void
    {
        $routes = $this->createRoutes();

        $routes->get('/x', 'strtoupper');

        $this->assertInstanceOf(Closure::class, $routes->routes()[0]->handler);
    }

    public function testFirstClassCallableClosureIsAcceptedAsIs(): void
    {
        $routes = $this->createRoutes();
        $factory = new Psr17Factory();
        $response = $factory->createResponse(200);
        $handler = new InvokableEchoHandler($response);

        $routes->get('/x', $handler(...));

        $route = $routes->routes()[0];
        $this->assertInstanceOf(Closure::class, $route->handler);
        $this->assertSame($response, ($route->handler)($factory->createServerRequest('GET', '/x')));
    }

    public function testInvokableObjectIsConvertedToClosure(): void
    {
        $routes = $this->createRoutes();
        $factory = new Psr17Factory();
        $response = $factory->createResponse(200);
        $invokable = new InvokableEchoHandler($response);

        $routes->post('/x', $invokable);

        $route = $routes->routes()[0];
        $this->assertInstanceOf(Closure::class, $route->handler);
        $this->assertSame($response, ($route->handler)($factory->createServerRequest('POST', '/x')));
    }

    public function testRequestHandlerInterfaceIsKeptAsIsWithoutConversion(): void
    {
        $routes = $this->createRoutes();
        $factory = new Psr17Factory();
        $handler = new FixedResponseHandler($factory->createResponse(200));

        $routes->get('/x', $handler);

        $this->assertSame($handler, $routes->routes()[0]->handler);
    }

    public function testMiddlewareCollectsGlobalMiddlewareInRegistrationOrder(): void
    {
        $routes = $this->createRoutes();
        $first = new NoopMiddleware();
        $second = new NoopMiddleware();

        $routes->middleware($first);
        $routes->middleware($second);

        $this->assertSame([$first, $second], $routes->globalMiddlewares());
    }

    public function testRouteSpecificMiddlewareStartsEmptyOnEveryRegisteredRoute(): void
    {
        $routes = $this->createRoutes();

        $routes->get('/x', static fn () => null);

        $this->assertSame([], $routes->routes()[0]->middleware);
    }

    public function testRouteSpecificMiddlewareIsCollectedInRegistrationOrder(): void
    {
        $routes = $this->createRoutes();
        $first = new NoopMiddleware();
        $second = new NoopMiddleware();

        $routes->get('/x', static fn () => null, $first, $second);

        $this->assertSame([$first, $second], $routes->routes()[0]->middleware);
    }

    public function testRouteSpecificMiddlewareIsAcceptedByEveryVerbMethod(): void
    {
        $routes = $this->createRoutes();
        $middleware = new NoopMiddleware();

        $routes->post('/a', static fn () => null, $middleware);
        $routes->put('/b', static fn () => null, $middleware);
        $routes->patch('/c', static fn () => null, $middleware);
        $routes->delete('/d', static fn () => null, $middleware);

        foreach ($routes->routes() as $route) {
            $this->assertSame([$middleware], $route->middleware);
        }
    }

    public function testHealthRegistersGetRouteWithInternalHandlerReturning200Json(): void
    {
        $routes = $this->createRoutes();

        $routes->health('/health');

        $registered = $routes->routes();
        $this->assertCount(1, $registered);
        $this->assertSame('GET', $registered[0]->method);
        $this->assertSame('/health', $registered[0]->path);

        $factory = new Psr17Factory();
        $handler = $registered[0]->handler;
        assert($handler instanceof Closure);
        $response = $handler($factory->createServerRequest('GET', '/health'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"status":200}', (string) $response->getBody());
    }
}
