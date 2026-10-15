<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use InvalidArgumentException;
use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Contract\SessionGuardInterface;
use JardisCore\App\Middleware\RequireRequestAttribute;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\FakeAuthenticator;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Contract\Auth\RequestAttribute;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration tests for RequireRequestAttribute through the real App pipeline
 * (global RequireSession, the attribute check per route). Only the
 * AuthenticatorInterface port is faked.
 */
final class RequireRequestAttributeTest extends TestCase
{
    private Psr17Factory $factory;
    /** @var list<ServerRequestInterface> */
    private array $handled = [];

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
        $this->handled = [];
    }

    private function app(): App
    {
        $authenticator = new FakeAuthenticator([
            'with-tenant' => ['alice', 'acme'],
            'no-tenant' => ['bob', null],
        ]);
        $psr17 = $this->factory;
        $handler = function (ServerRequestInterface $request): ResponseInterface {
            $this->handled[] = $request;

            return $this->factory->createResponse(200);
        };

        $routes = new Routes($psr17);
        $routes->middleware(new RequireSession($authenticator, FakeAuthenticator::credentialFor(...), $psr17, $psr17));
        $routes->get('/tenant', $handler, new RequireRequestAttribute($psr17, $psr17, RequestAttribute::TENANT));
        $routes->get(
            '/both',
            $handler,
            new RequireRequestAttribute($psr17, $psr17, RequestAttribute::TENANT, RequestAttribute::ROLE),
        );
        $routes->get(
            '/forge/{jardisTenant}',
            $handler,
            new RequireRequestAttribute($psr17, $psr17, RequestAttribute::TENANT),
        );

        return new App($routes, new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'), new AppConfig());
    }

    private function get(App $app, string $path, ?string $token = null, string $method = 'GET'): ResponseInterface
    {
        $request = $this->factory->createServerRequest($method, $path);
        if ($token !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $token);
        }

        return $app->handle($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function testSessionWithoutTenantIsAnswered403WithTheMissingAttributeForm(): void
    {
        $response = $this->get($this->app(), '/tenant', 'no-tenant');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $body = $this->decode($response);
        $this->assertSame(['status', 'data', 'errors', 'meta'], array_keys($body));
        $this->assertSame(403, $body['status']);
        $this->assertSame([], $body['data']);
        $this->assertSame([], $body['meta']);
        $this->assertSame(RequireRequestAttribute::MESSAGE_KEY, $body['errors']['messageKey']);
        $this->assertSame('request.attributeMissing', $body['errors']['messageKey']);
        $this->assertSame(['attribute' => RequestAttribute::TENANT], $body['errors']['context']);
        $this->assertIsString($body['errors']['message']);
        $this->assertArrayNotHasKey('@type', $body['data']);
        $this->assertSame([], $this->handled);
    }

    public function testSessionWithTenantReachesTheHandlerWithTheAttribute(): void
    {
        $response = $this->get($this->app(), '/tenant', 'with-tenant');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $this->handled);
        $this->assertSame('acme', $this->handled[0]->getAttribute(RequestAttribute::TENANT));
    }

    public function testWithoutTokenTheSessionDoorAnswers401NotTheAttributeCheck(): void
    {
        $response = $this->get($this->app(), '/tenant');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Authentication required.', $this->decode($response)['errors']['message']);
        $this->assertSame([], $this->handled);
    }

    public function testWithSeveralNamesTheFirstMissingOneIsNamed(): void
    {
        // with-tenant has a tenant but no role: only the second name is missing.
        $second = $this->decode($this->get($this->app(), '/both', 'with-tenant'));
        $this->assertSame(['attribute' => RequestAttribute::ROLE], $second['errors']['context']);

        // no-tenant misses both: the first one in the given order wins.
        $first = $this->decode($this->get($this->app(), '/both', 'no-tenant'));
        $this->assertSame(['attribute' => RequestAttribute::TENANT], $first['errors']['context']);
        $this->assertSame([], $this->handled);
    }

    public function testClientCannotSatisfyTheCheckViaAPathPlaceholder(): void
    {
        $response = $this->get($this->app(), '/forge/evil', 'no-tenant');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([], $this->handled);
    }

    public function testUnknownPathStays404AndWrongMethodStays405(): void
    {
        $app = $this->app();

        $this->assertSame(404, $this->get($app, '/nowhere', 'no-tenant')->getStatusCode());
        $this->assertSame(405, $this->get($app, '/tenant', 'no-tenant', 'POST')->getStatusCode());
    }

    public function testEmptyNameListThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequireRequestAttribute($this->factory, $this->factory);
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequireRequestAttribute($this->factory, $this->factory, RequestAttribute::TENANT, '');
    }

    public function testItIsNotASessionGuard(): void
    {
        $this->assertNotContains(
            SessionGuardInterface::class,
            (array) class_implements(RequireRequestAttribute::class),
            'RequireRequestAttribute must not carry the session-guard marker.',
        );
    }

    public function testItDoesNotSatisfyTheRequireSessionGuardStartupCheck(): void
    {
        $routes = new Routes($this->factory);
        $routes->get(
            '/tenant',
            fn (): ResponseInterface => $this->factory->createResponse(200),
            new RequireRequestAttribute($this->factory, $this->factory, RequestAttribute::TENANT),
        );

        $this->expectException(\LogicException::class);

        new App(
            $routes,
            new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'),
            new AppConfig(requireSessionGuard: true),
        );
    }
}
