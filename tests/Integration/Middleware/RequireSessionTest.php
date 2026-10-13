<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
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
 * Integration tests for RequireSession through the real App pipeline
 * (Routes -> Router -> global middleware -> handler). Only the
 * AuthenticatorInterface port is faked.
 */
final class RequireSessionTest extends TestCase
{
    private Psr17Factory $factory;
    private FakeAuthenticator $authenticator;
    /** @var list<ServerRequestInterface> */
    private array $handled = [];

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
        $this->handled = [];
        $this->authenticator = new FakeAuthenticator([
            'good' => ['alice', 'acme'],
            'no-tenant' => ['bob', null],
            'empty-tenant' => ['carol', ''],
            'no-subject' => [null, 'acme'],
        ]);
    }

    /**
     * @param list<string> $anonymous
     */
    private function app(array $anonymous = ['/login', '/public/*'], bool $global = true, string $scheme = 'Bearer', string $header = 'Authorization'): App
    {
        $session = new RequireSession(
            $this->authenticator,
            FakeAuthenticator::credentialFor(...),
            $this->factory,
            $this->factory,
            $anonymous,
            $header,
            $scheme,
        );

        $routes = new Routes($this->factory);
        $handler = function (ServerRequestInterface $request): ResponseInterface {
            $this->handled[] = $request;
            $response = $this->factory->createResponse(200);
            $response->getBody()->write('handled');

            return $response;
        };

        if ($global) {
            $routes->middleware($session);
            $routes->get('/orders', $handler);
            $routes->get('/echo/{jardisSubject}/{jardisTenant}/{jardisAnonymousRoute}', $handler);
            $routes->get('/login', $handler);
            $routes->get('/public/info', $handler);
            $routes->health('/health');
        } else {
            $routes->get('/guarded', $handler, $session);
            $routes->get('/open', $handler);
        }

        return new App($routes, new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'), new AppConfig());
    }

    private function resetHandled(): void
    {
        $this->handled = [];
    }

    private function get(App $app, string $path, ?string $authorization = null, string $header = 'Authorization'): ResponseInterface
    {
        $request = $this->factory->createServerRequest('GET', $path);
        if ($authorization !== null) {
            $request = $request->withHeader($header, $authorization);
        }

        return $app->handle($request);
    }

    public function testValidSessionReachesTheHandlerWithSubjectAndTenant(): void
    {
        $response = $this->get($this->app(), '/orders', 'Bearer good');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $this->handled);
        $this->assertSame('alice', $this->handled[0]->getAttribute(RequestAttribute::SUBJECT));
        $this->assertSame('acme', $this->handled[0]->getAttribute(RequestAttribute::TENANT));
        $this->assertSame('good', $this->authenticator->seen[0]->getValue());
    }

    public function testSessionWithoutTenantLeavesTheTenantAttributeAbsent(): void
    {
        foreach (['no-tenant', 'empty-tenant'] as $token) {
            $this->resetHandled();
            $this->get($this->app(), '/orders', 'Bearer ' . $token);

            $this->assertCount(1, $this->handled, $token);
            $this->assertArrayHasKey(RequestAttribute::SUBJECT, $this->handled[0]->getAttributes());
            $this->assertArrayNotHasKey(RequestAttribute::TENANT, $this->handled[0]->getAttributes(), $token);
        }
    }

    public function testMissingHeaderAnswers401InTheCanonicalEnvelopeAndNeverReachesTheHandler(): void
    {
        $response = $this->get($this->app(), '/orders');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Bearer', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['status', 'data', 'errors', 'meta'], array_keys($body));
        $this->assertSame(401, $body['status']);
        $this->assertSame('Authentication required.', $body['errors']['message']);
        $this->assertSame([], $this->handled);
    }

    public function testEveryKindOfBadCredentialIsAnsweredIdentically(): void
    {
        $app = $this->app();
        $bodies = [];
        foreach (['Bearer revoked-or-expired', 'Bearer', 'Bearer ', 'Basic good', 'good', 'Bearer a b', 'Bearer no-subject'] as $value) {
            $response = $this->get($app, '/orders', $value);
            $this->assertSame(401, $response->getStatusCode(), $value);
            $bodies[$value] = (string) $response->getBody();
        }

        $this->assertCount(1, array_unique($bodies));
        $this->assertSame([], $this->handled);
    }

    public function testRepeatedAuthorizationHeaderIsRejected(): void
    {
        $request = $this->factory->createServerRequest('GET', '/orders')
            ->withHeader('Authorization', 'Bearer good')
            ->withAddedHeader('Authorization', 'Bearer good');

        $this->assertSame(401, $this->app()->handle($request)->getStatusCode());
        $this->assertSame([], $this->handled);
    }

    public function testSchemeIsMatchedCaseInsensitively(): void
    {
        $this->assertSame(200, $this->get($this->app(), '/orders', 'bearer good')->getStatusCode());
    }

    public function testAnonymousPathsPassWithoutSessionExactAndByPrefix(): void
    {
        $app = $this->app();

        $this->assertSame(200, $this->get($app, '/login')->getStatusCode());
        $this->assertSame(200, $this->get($app, '/public/info')->getStatusCode());
        $this->assertCount(2, $this->handled);
        $this->assertSame([], $this->authenticator->seen);
    }

    public function testExactAnonymousPathDoesNotOpenSimilarPaths(): void
    {
        $app = $this->app(['/orders']);

        $this->assertSame(200, $this->get($app, '/orders')->getStatusCode());
        $this->assertSame(401, $this->get($app, '/login')->getStatusCode());
    }

    public function testHealthAnswers200WithoutSessionEvenWhenNotListedAsAnonymous(): void
    {
        $response = $this->get($this->app([]), '/health');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"status":200}', (string) $response->getBody());
    }

    public function testUnknownPathIsStill404AndWrongMethodStill405NotUnauthorized(): void
    {
        $app = $this->app();

        $this->assertSame(404, $this->get($app, '/nowhere')->getStatusCode());

        $post = $app->handle($this->factory->createServerRequest('POST', '/orders'));
        $this->assertSame(405, $post->getStatusCode());
    }

    public function testClientCannotForgeSubjectTenantOrAnonymousViaPathPlaceholders(): void
    {
        $response = $this->get($this->app(), '/echo/mallory/evil/1', 'Bearer good');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('alice', $this->handled[0]->getAttribute(RequestAttribute::SUBJECT));
        $this->assertSame('acme', $this->handled[0]->getAttribute(RequestAttribute::TENANT));

        $this->resetHandled();
        $this->authenticator->seen = [];
        $unauthenticated = $this->get($this->app(), '/echo/mallory/evil/true');

        $this->assertSame(401, $unauthenticated->getStatusCode());
        $this->assertSame([], $this->handled);
    }

    public function testClientAttributesAreStrippedOnAnonymousPaths(): void
    {
        $routes = new Routes($this->factory);
        $routes->middleware(new RequireSession(
            $this->authenticator,
            FakeAuthenticator::credentialFor(...),
            $this->factory,
            $this->factory,
            ['/open/*'],
        ));
        $routes->get('/open/{jardisSubject}', function (ServerRequestInterface $request): ResponseInterface {
            $this->handled[] = $request;

            return $this->factory->createResponse(200);
        });
        $app = new App($routes, new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'), new AppConfig());

        $this->get($app, '/open/mallory');

        $this->assertCount(1, $this->handled);
        $this->assertArrayNotHasKey(RequestAttribute::SUBJECT, $this->handled[0]->getAttributes());
        $this->assertArrayNotHasKey(RequestAttribute::TENANT, $this->handled[0]->getAttributes());
    }

    public function testAuthenticatorFailureIsATechnicalErrorNot401(): void
    {
        $response = $this->get($this->app(), '/orders', 'Bearer boom');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame([], $this->handled);
    }

    public function testPerRouteRegistrationGuardsOnlyThatRoute(): void
    {
        $app = $this->app(global: false);

        $this->assertSame(401, $this->get($app, '/guarded')->getStatusCode());
        $this->assertSame(200, $this->get($app, '/guarded', 'Bearer good')->getStatusCode());
        $this->assertSame(200, $this->get($app, '/open')->getStatusCode());
    }

    public function testCustomHeaderWithoutSchemeTakesTheWholeValueAsToken(): void
    {
        $app = $this->app(scheme: '', header: 'X-Api-Token');

        $this->assertSame(200, $this->get($app, '/orders', 'good', 'X-Api-Token')->getStatusCode());
        $unauthorized = $this->get($app, '/orders');
        $this->assertSame(401, $unauthorized->getStatusCode());
        $this->assertFalse($unauthorized->hasHeader('WWW-Authenticate'));
    }
}
