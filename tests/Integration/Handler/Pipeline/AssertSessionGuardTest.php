<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Pipeline;

use JardisCore\App\Handler\Pipeline\AssertSessionGuard;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\FakeAuthenticator;
use JardisCore\App\Tests\Support\PlainMiddleware;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Tests for the start-up session-guard check: the switch, the global/route chain and the
 * anonymous exemption, against real Routes and real RequireSession instances.
 */
final class AssertSessionGuardTest extends TestCase
{
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
    }

    private function guard(): MiddlewareInterface
    {
        return new RequireSession(new FakeAuthenticator([]), FakeAuthenticator::credentialFor(...), $this->psr17, $this->psr17);
    }

    private function handler(): callable
    {
        $psr17 = $this->psr17;

        return static fn(ServerRequestInterface $request): ResponseInterface => $psr17->createResponse(200);
    }

    private function check(bool $enabled, Routes $routes): void
    {
        (new AssertSessionGuard($enabled))($routes->routes(), $routes->globalMiddlewares());
    }

    public function testSwitchOffAcceptsUnguardedSessionRoute(): void
    {
        $routes = new Routes($this->psr17);
        $routes->post('/orders', $this->handler());

        $this->check(false, $routes);

        $this->addToAssertionCount(1);
    }

    public function testSwitchOnWithoutMiddlewareThrowsAndNamesRoutes(): void
    {
        $routes = new Routes($this->psr17);
        $routes->post('/orders', $this->handler());
        $routes->get('/customers', $this->handler());

        try {
            $this->check(true, $routes);
            $this->fail('Expected LogicException');
        } catch (LogicException $e) {
            $this->assertStringContainsString('POST /orders', $e->getMessage());
            $this->assertStringContainsString('GET /customers', $e->getMessage());
            $this->assertStringContainsString('session', $e->getMessage());
            $this->assertStringContainsString('Routes::anonymous()', $e->getMessage());
        }
    }

    public function testSwitchOnAcceptsGlobalGuard(): void
    {
        $routes = new Routes($this->psr17);
        $routes->middleware($this->guard());
        $routes->post('/orders', $this->handler());

        $this->check(true, $routes);

        $this->addToAssertionCount(1);
    }

    public function testSwitchOnAcceptsGuardOnEveryNonAnonymousRoute(): void
    {
        $guard = $this->guard();
        $routes = new Routes($this->psr17);
        $routes->post('/orders', $this->handler(), $guard);
        $routes->get('/orders/{id}', $this->handler(), $guard);
        $routes->anonymous()->post('/login', $this->handler());

        $this->check(true, $routes);

        $this->addToAssertionCount(1);
    }

    public function testSwitchOnRejectsRouteMissingItsOwnGuard(): void
    {
        $guard = $this->guard();
        $routes = new Routes($this->psr17);
        $routes->post('/orders', $this->handler(), $guard);
        $routes->get('/orders/{id}', $this->handler());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('GET /orders/{id}');

        $this->check(true, $routes);
    }

    public function testSwitchOnAcceptsOnlyAnonymousRoutesAndHealth(): void
    {
        $routes = new Routes($this->psr17);
        $routes->health('/health');
        $routes->anonymous()->get('/docs', $this->handler());

        $this->check(true, $routes);

        $this->addToAssertionCount(1);
    }

    public function testSwitchOnRejectsPlainMiddlewareWithoutGuard(): void
    {
        $routes = new Routes($this->psr17);
        $routes->middleware(new PlainMiddleware());
        $routes->post('/orders', $this->handler());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('POST /orders');

        $this->check(true, $routes);
    }
}
