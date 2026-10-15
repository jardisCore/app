<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\Data\Subject;
use JardisSupport\Auth\SessionManager;
use JardisSupport\Auth\SignedTokenAuthenticator;
use JardisSupport\Contract\Auth\RequestAttribute;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration test of the session flow with signed access tokens: the token is issued by the real
 * SessionManager, verified by the real SignedTokenAuthenticator behind RequireSession, and the requests
 * run through the real App pipeline. The request path touches no database and no token store - the
 * authenticator is constructed from the signing key alone.
 */
final class SessionSignedTokenTest extends TestCase
{
    private Psr17Factory $psr17;
    private string $key;
    private ?ServerRequestInterface $seen = null;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->key = random_bytes(32);
        $this->seen = null;
    }

    public function testSignedTokenOpensTheProtectedRoute(): void
    {
        $app = $this->app();

        $response = $app->handle($this->request('/orders', $this->issue()));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHandlerSeesSubjectTenantAndSessionOfTheToken(): void
    {
        $this->app()->handle($this->request('/orders', $this->issue(['tenant' => 'acme'])));

        $this->assertNotNull($this->seen);
        $this->assertSame('user:42', $this->seen->getAttribute(RequestAttribute::SUBJECT));
        $this->assertSame('42', $this->seen->getAttribute(RequestAttribute::SUBJECT_ID));
        $this->assertSame('acme', $this->seen->getAttribute(RequestAttribute::TENANT));
        $this->assertNotEmpty($this->seen->getAttribute(RequestAttribute::SESSION));
    }

    public function testHandlerSeesTheRoleClaimOfTheToken(): void
    {
        $this->app()->handle($this->request('/orders', $this->issue(['tenant' => 'acme', 'role' => 'owner'])));

        $this->assertNotNull($this->seen);
        $this->assertSame('owner', $this->seen->getAttribute(RequestAttribute::ROLE));
    }

    public function testRoleAttributeIsAbsentWithoutRoleClaim(): void
    {
        $this->app()->handle($this->request('/orders', $this->issue(['tenant' => 'acme'])));

        $this->assertNotNull($this->seen);
        $this->assertArrayNotHasKey(RequestAttribute::ROLE, $this->seen->getAttributes());
    }

    public function testClientSuppliedRoleIsRemovedWhenTheTokenCarriesNone(): void
    {
        $this->app()->handle($this->request('/forge/admin', $this->issue()));

        $this->assertNotNull($this->seen);
        $this->assertArrayNotHasKey(RequestAttribute::ROLE, $this->seen->getAttributes());
    }

    public function testClientSuppliedRoleIsOverwrittenByTheTokenRole(): void
    {
        $this->app()->handle($this->request('/forge/admin', $this->issue(['role' => 'viewer'])));

        $this->assertNotNull($this->seen);
        $this->assertSame('viewer', $this->seen->getAttribute(RequestAttribute::ROLE));
    }

    public function testRequestWithoutTokenIsRejectedWith401(): void
    {
        $response = $this->app()->handle($this->request('/orders'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Bearer', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertNull($this->seen);
    }

    public function testTamperedTokenIsRejectedWith401(): void
    {
        $token = $this->issue();
        [$payload, $mac] = explode('.', $token, 2);
        $tamperedMac = $mac[0] === 'A' ? 'B' . substr($mac, 1) : 'A' . substr($mac, 1);

        $response = $this->app()->handle($this->request('/orders', $payload . '.' . $tamperedMac));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertNull($this->seen);
    }

    public function testTokenSignedWithAnotherKeyIsRejectedWith401(): void
    {
        $foreign = (new SessionManager(new InMemoryTokenStore(), random_bytes(32)))
            ->create(Subject::from('42', 'user'))->accessToken;

        $response = $this->app()->handle($this->request('/orders', $foreign));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testAnonymousRoutePassesWithoutToken(): void
    {
        $response = $this->app()->handle($this->request('/open'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function issue(array $claims = []): string
    {
        return (new SessionManager(new InMemoryTokenStore(), $this->key))
            ->create(Subject::from('42', 'user'), $claims)
            ->accessToken;
    }

    private function app(): App
    {
        $routes = new Routes($this->psr17);
        $routes->middleware(new RequireSession(
            new SignedTokenAuthenticator($this->key),
            Credential::token(...),
            $this->psr17,
            $this->psr17,
        ));
        $handler = function (ServerRequestInterface $request): ResponseInterface {
            $this->seen = $request;

            return $this->psr17->createResponse(200);
        };
        $routes->anonymous()->get('/open', $handler);
        $routes->get('/orders', $handler);
        $routes->get('/forge/{jardisRole}', $handler);

        return new App(
            $routes,
            new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'),
            new AppConfig(requireSessionGuard: true),
        );
    }

    private function request(string $path, ?string $bearer = null): ServerRequestInterface
    {
        $request = $this->psr17->createServerRequest('GET', $path);

        return $bearer === null ? $request : $request->withHeader('Authorization', 'Bearer ' . $bearer);
    }
}
