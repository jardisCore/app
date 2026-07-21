<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration;

use Closure;
use JardisCore\App\Data\Route;
use JardisCore\App\Data\RouteMatch;
use JardisCore\App\Data\RouteMatchStatus;
use JardisCore\App\Handler\Routing\WrapCallableHandler;
use JardisCore\App\Router;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\FixedResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tests for the Router orchestrator (F1): match with path parameters
 * (F8a), NotFound, MethodNotAllowed with a full allowed-methods list,
 * HEAD-auto-registration (E13), trailing-slash/case-sensitivity match
 * semantics, and OPTIONS falling into the 404/405 mechanic (E14).
 */
final class RouterTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    private function request(string $method, string $path): ServerRequestInterface
    {
        return $this->factory->createServerRequest($method, $path);
    }

    /**
     * A handler that echoes the `id` request attribute into the response
     * body - used to prove the F8a recipe (Router stays a pure matcher;
     * applying withAttribute() for RouteMatch::found()'s parameters is the
     * call chain's job) actually works end to end.
     */
    private function attributeEchoingHandler(): Closure
    {
        $factory = $this->factory;

        return function (ServerRequestInterface $request) use ($factory): ResponseInterface {
            $response = $factory->createResponse(200);
            $response->getBody()->write((string) $request->getAttribute('id'));

            return $response;
        };
    }

    public function testMatchesRouteWithPathParametersAndAvailableAsRequestAttributesViaTheRecipe(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders/{id}', $this->attributeEchoingHandler());
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('GET', '/orders/42'));

        $this->assertSame(RouteMatchStatus::Found, $match->status);
        $this->assertSame(['id' => '42'], $match->parameters);
        $this->assertInstanceOf(Route::class, $match->route);

        // The documented F8a recipe: the Pipeline/App call chain applies
        // withAttribute() for every RouteMatch parameter before invoking
        // the matched handler - proven here directly, since Pipeline/App
        // are out of P3's scope.
        $request = $this->request('GET', '/orders/42');
        foreach ($match->parameters as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $wrapped = (new WrapCallableHandler())($match->route->handler);
        $response = $wrapped->handle($request);

        $this->assertSame('42', (string) $response->getBody());
    }

    public function testUnknownPathReturnsNotFound(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders', static fn () => null);
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('GET', '/unknown'));

        $this->assertSame(RouteMatchStatus::NotFound, $match->status);
        $this->assertNull($match->route);
    }

    public function testKnownPathWithUnregisteredMethodReturnsMethodNotAllowedWithFullAllowedList(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders', static fn () => null);
        $routes->post('/orders', static fn () => null);
        $routes->put('/orders', static fn () => null);
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('DELETE', '/orders'));

        $this->assertSame(RouteMatchStatus::MethodNotAllowed, $match->status);
        // GET auto-registers HEAD (E13), so the allowed list also carries HEAD.
        $this->assertEqualsCanonicalizing(['GET', 'HEAD', 'POST', 'PUT'], $match->allowedMethods);
    }

    public function testHeadRequestAutoMatchesTheGetRouteHandler(): void
    {
        $handler = new FixedResponseHandler($this->factory->createResponse(200));
        $routes = new Routes($this->factory);
        $routes->get('/orders/{id}', $handler);
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('HEAD', '/orders/7'));

        $this->assertSame(RouteMatchStatus::Found, $match->status);
        $this->assertSame(['id' => '7'], $match->parameters);
        $this->assertSame($handler, $match->route?->handler);
    }

    public function testExplicitHeadRouteIsUsedInsteadOfAutoRegisteredOneAndIsNotDuplicated(): void
    {
        $getHandler = new FixedResponseHandler($this->factory->createResponse(200));
        $explicitHeadHandler = new FixedResponseHandler($this->factory->createResponse(204));

        $routes = new Routes($this->factory);
        $routes->get('/orders/{id}', $getHandler);

        // Routes has no public head() registration method (out of P3 scope);
        // register the explicit HEAD route directly as a Route VO via the
        // Router's constructor input instead, to prove BuildDispatcher
        // detects an already-explicit HEAD and skips auto-registration.
        $allRoutes = [...$routes->routes(), new Route('HEAD', '/orders/{id}', $explicitHeadHandler)];
        $router = new Router($allRoutes);

        $match = $router->dispatch($this->request('HEAD', '/orders/9'));

        $this->assertSame(RouteMatchStatus::Found, $match->status);
        $this->assertSame($explicitHeadHandler, $match->route?->handler);
    }

    public function testTrailingSlashIsNotEquivalentToPathWithoutSlash(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/x', static fn () => null);
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('GET', '/x/'));

        $this->assertSame(RouteMatchStatus::NotFound, $match->status);
    }

    public function testPathMatchingIsCaseSensitive(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders', static fn () => null);
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('GET', '/Orders'));

        $this->assertSame(RouteMatchStatus::NotFound, $match->status);
    }

    public function testOptionsWithoutAnyRouteOnThePathFallsIntoNotFound(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders', static fn () => null);
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('OPTIONS', '/unregistered'));

        $this->assertSame(RouteMatchStatus::NotFound, $match->status);
    }

    public function testOptionsOnARegisteredPathWithoutAnOptionsRouteFallsIntoMethodNotAllowedWithoutAutoOptions(): void
    {
        $routes = new Routes($this->factory);
        $routes->get('/orders', static fn () => null);
        $router = new Router($routes->routes());

        $match = $router->dispatch($this->request('OPTIONS', '/orders'));

        $this->assertSame(RouteMatchStatus::MethodNotAllowed, $match->status);
        $this->assertEqualsCanonicalizing(['GET', 'HEAD'], $match->allowedMethods);
        $this->assertNotContains('OPTIONS', $match->allowedMethods);
    }
}
