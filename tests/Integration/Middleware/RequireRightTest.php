<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use InvalidArgumentException;
use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Contract\SessionGuardInterface;
use JardisCore\App\Middleware\RequireRight;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\CountingRequestHandler;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\Data\Subject;
use JardisSupport\Auth\Handler\Authorization\HasRight;
use JardisSupport\Auth\SessionManager;
use JardisSupport\Auth\SignedTokenAuthenticator;
use JardisSupport\Contract\Auth\RequestAttribute;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration tests for RequireRight through the real App pipeline: the global RequireSession (real
 * SignedTokenAuthenticator, token issued by the real SessionManager with a rights claim) sets
 * RequestAttribute::RIGHTS, the route's RequireRight asks the real HasRight of jardissupport/auth.
 * Nothing is faked; the unusable-RIGHTS cases call the middleware directly because no session can produce them.
 */
final class RequireRightTest extends TestCase
{
    private const RIGHT = 'sales.order:read';

    private Psr17Factory $psr17;
    private string $key;
    /** @var list<ServerRequestInterface> */
    private array $handled = [];

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->key = random_bytes(32);
        $this->handled = [];
    }

    public function testSessionHoldingTheRightReachesTheHandler(): void
    {
        $response = $this->app()->handle($this->request('/orders', $this->issue(['sales.order:read'])));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $this->handled);
    }

    public function testResourceWildcardPassesLikeTheExactRight(): void
    {
        $response = $this->app()->handle($this->request('/orders', $this->issue(['sales.order:*'])));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $this->handled);
    }

    public function testSuperuserStarPassesEveryRoute(): void
    {
        $token = $this->issue(['*']);
        $app = $this->app();

        $this->assertSame(200, $app->handle($this->request('/orders', $token))->getStatusCode());
        $this->assertSame(200, $app->handle($this->request('/invoices', $token))->getStatusCode());
        $this->assertCount(2, $this->handled);
    }

    public function testSessionLackingTheRightIsAnswered403WithTheExactBytes(): void
    {
        $response = $this->app()->handle($this->request('/orders', $this->issue(['sales.invoice:read'])));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(
            '{"status":403,"data":{},"errors":{"message":"Right \"sales.order:read\" is missing.",'
            . '"messageKey":"access.rightMissing","context":{"right":"sales.order:read"}},"meta":{}}',
            (string) $response->getBody(),
        );
        $this->assertSame([], $this->handled, 'the handler is not called on a denial');
    }

    public function testRightOfAnotherResourceDoesNotMatchThroughAWildcard(): void
    {
        $response = $this->app()->handle($this->request('/orders', $this->issue(['sales.invoice:*'])));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([], $this->handled);
    }

    public function testDenialNamesTheRightOfTheRouteNotOfTheSession(): void
    {
        $response = $this->app()->handle($this->request('/invoices', $this->issue(['sales.order:read'])));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            ['right' => 'sales.invoice:read'],
            $this->decode($response)['errors']['context'],
        );
    }

    public function testSessionWithoutRightsClaimIsAnswered403(): void
    {
        $response = $this->app()->handle($this->request('/orders', $this->issue(null)));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('access.rightMissing', $this->decode($response)['errors']['messageKey']);
        $this->assertSame([], $this->handled);
    }

    public function testSessionWithEmptyRightsListIsAnswered403(): void
    {
        $response = $this->app()->handle($this->request('/orders', $this->issue([])));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([], $this->handled);
    }

    public function testClientCannotGrantARightViaAPathPlaceholder(): void
    {
        $response = $this->app()->handle($this->request('/forge/sales.order:read', $this->issue(['sales.invoice:read'])));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([], $this->handled);
    }

    public function testGuestIsAnswered401ByTheSessionDoorNotByTheRightCheck(): void
    {
        $response = $this->app()->handle($this->request('/orders'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Bearer', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertSame([], $this->handled);
    }

    public function testUnknownPathStays404AndWrongMethodStays405(): void
    {
        $app = $this->app();
        $token = $this->issue(['*']);

        $this->assertSame(404, $app->handle($this->request('/nowhere', $token))->getStatusCode());
        $this->assertSame(405, $app->handle($this->request('/orders', $token, 'POST'))->getStatusCode());
    }

    public function testRightsAttributeThatIsNotAListOfStringsIsAnswered403(): void
    {
        foreach (['sales.order:read', 42, ['sales.order:read', 7], ['right' => 'sales.order:read']] as $unusable) {
            $handler = new CountingRequestHandler($this->psr17);
            $request = $this->psr17->createServerRequest('GET', '/orders')
                ->withAttribute(RequestAttribute::RIGHTS, $unusable);

            $response = $this->rightMiddleware()->process($request, $handler);

            $this->assertSame(403, $response->getStatusCode(), var_export($unusable, true));
            $this->assertSame(0, $handler->calls);
        }
    }

    public function testMissingRightsAttributeIsAnswered403FailClosed(): void
    {
        $handler = new CountingRequestHandler($this->psr17);

        $response = $this->rightMiddleware()->process($this->psr17->createServerRequest('GET', '/orders'), $handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['right' => self::RIGHT], $this->decode($response)['errors']['context']);
        $this->assertSame(0, $handler->calls);
    }

    public function testGrantedRightThatCannotBeParsedIsAnswered403NotAnException(): void
    {
        $handler = new CountingRequestHandler($this->psr17);
        $request = $this->psr17->createServerRequest('GET', '/orders')
            ->withAttribute(RequestAttribute::RIGHTS, ['not-a-right']);

        $response = $this->rightMiddleware()->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(0, $handler->calls);
    }

    public function testTheCheckReceivesTheRightsOfTheSessionAndTheRightOfTheRoute(): void
    {
        $seen = [];
        $middleware = new RequireRight(
            $this->psr17,
            $this->psr17,
            self::RIGHT,
            static function (array $rights, string $right) use (&$seen): bool {
                $seen = [$rights, $right];

                return true;
            },
        );
        $handler = new CountingRequestHandler($this->psr17);

        $middleware->process(
            $this->psr17->createServerRequest('GET', '/orders')->withAttribute(RequestAttribute::RIGHTS, ['a:b', 'c:d']),
            $handler,
        );

        $this->assertSame([['a:b', 'c:d'], self::RIGHT], $seen);
        $this->assertSame(1, $handler->calls);
    }

    public function testEmptyRightThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RequireRight($this->psr17, $this->psr17, '', (new HasRight())->__invoke(...));
    }

    public function testItIsNotASessionGuard(): void
    {
        $this->assertNotContains(
            SessionGuardInterface::class,
            (array) class_implements(RequireRight::class),
            'RequireRight must not carry the session-guard marker.',
        );
    }

    private function rightMiddleware(): RequireRight
    {
        return new RequireRight($this->psr17, $this->psr17, self::RIGHT, (new HasRight())->__invoke(...));
    }

    /**
     * @param list<string>|null $rights null = a token without rights claim
     */
    private function issue(?array $rights): string
    {
        return (new SessionManager(new InMemoryTokenStore(), $this->key))
            ->create(Subject::from('42', 'user'), $rights === null ? [] : ['rights' => $rights])
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
            $this->handled[] = $request;

            return $this->psr17->createResponse(200);
        };
        $hasRight = (new HasRight())->__invoke(...);
        $routes->get('/orders', $handler, new RequireRight($this->psr17, $this->psr17, self::RIGHT, $hasRight));
        $routes->get('/invoices', $handler, new RequireRight($this->psr17, $this->psr17, 'sales.invoice:read', $hasRight));
        $routes->get(
            '/forge/{jardisRights}',
            $handler,
            new RequireRight($this->psr17, $this->psr17, self::RIGHT, $hasRight),
        );

        return new App(
            $routes,
            new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'),
            new AppConfig(requireSessionGuard: true),
        );
    }

    private function request(string $path, ?string $bearer = null, string $method = 'GET'): ServerRequestInterface
    {
        $request = $this->psr17->createServerRequest($method, $path);

        return $bearer === null ? $request : $request->withHeader('Authorization', 'Bearer ' . $bearer);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}
