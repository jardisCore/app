<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Contract\SessionGuardInterface;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Middleware\RevokeSessionOnSuccess;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\FixedResponseHandler;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\Data\SessionResult;
use JardisSupport\Auth\Data\Subject;
use JardisSupport\Auth\Exception\AuthenticationException;
use JardisSupport\Auth\SessionManager;
use JardisSupport\Auth\SignedTokenAuthenticator;
use JardisSupport\Contract\Auth\RequestAttribute;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Integration tests for RevokeSessionOnSuccess: the middleware cases run against a plain request with the session
 * attribute; the end-to-end case runs the real App pipeline with the real RequireSession, SignedTokenAuthenticator
 * and SessionManager (jardissupport/auth) - after the logout answered 2xx, refreshing the session's refresh token fails.
 */
final class RevokeSessionOnSuccessTest extends TestCase
{
    private const REFERENCE = 'session-ref-1';

    private Psr17Factory $psr17;
    private string $key;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->key = random_bytes(32);
    }

    public function testSuccessWithReferenceRevokesExactlyOnceWithTheReference(): void
    {
        $calls = [];
        $middleware = $this->middleware($calls);

        $response = $middleware->process($this->requestWithReference(), $this->handlerAnswering(200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([self::REFERENCE], $calls);
    }

    public function testEveryTwoHundredStatusRevokes(): void
    {
        foreach ([200, 201, 204, 299] as $status) {
            $calls = [];

            $this->middleware($calls)->process($this->requestWithReference(), $this->handlerAnswering($status));

            $this->assertSame([self::REFERENCE], $calls, 'status ' . $status);
        }
    }

    public function testNonSuccessStatusDoesNotRevoke(): void
    {
        foreach ([199, 300, 302, 400, 401, 403, 404, 409, 422, 500, 503] as $status) {
            $calls = [];

            $response = $this->middleware($calls)->process($this->requestWithReference(), $this->handlerAnswering($status));

            $this->assertSame($status, $response->getStatusCode());
            $this->assertSame([], $calls, 'status ' . $status);
        }
    }

    public function testWithoutReferenceNothingIsRevoked(): void
    {
        $unusable = [null, '', 42, ['a'], true];
        foreach ($unusable as $value) {
            $calls = [];
            $request = $this->psr17->createServerRequest('POST', '/logout');
            if ($value !== null) {
                $request = $request->withAttribute(RequestAttribute::SESSION, $value);
            }

            $response = $this->middleware($calls)->process($request, $this->handlerAnswering(200));

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame([], $calls, var_export($value, true));
        }
    }

    public function testResponseIsReturnedUnchanged(): void
    {
        $calls = [];
        $answer = $this->psr17->createResponse(200)->withHeader('X-Marker', 'kept')
            ->withBody($this->psr17->createStream('{"status":200}'));

        $response = $this->middleware($calls)->process(
            $this->requestWithReference(),
            new FixedResponseHandler($answer),
        );

        $this->assertSame($answer, $response);
    }

    public function testExceptionFromRevokeIsNotSwallowed(): void
    {
        $middleware = new RevokeSessionOnSuccess(static function (string $reference): void {
            throw new RuntimeException('store down');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('store down');

        $middleware->process($this->requestWithReference(), $this->handlerAnswering(200));
    }

    public function testItIsNotASessionGuard(): void
    {
        $this->assertNotContains(
            SessionGuardInterface::class,
            (array) class_implements(RevokeSessionOnSuccess::class),
            'RevokeSessionOnSuccess must not carry the session-guard marker.',
        );
    }

    public function testLogoutRevokesTheRealSessionSoItsRefreshTokenFails(): void
    {
        $manager = new SessionManager(new InMemoryTokenStore(), $this->key);
        $session = $manager->create(Subject::from('42', 'user'));
        $other = $manager->create(Subject::from('42', 'user'));
        $app = $this->app($manager, 200);

        $response = $app->handle(
            $this->psr17->createServerRequest('POST', '/logout')
                ->withHeader('Authorization', 'Bearer ' . $session->accessToken),
        );

        $this->assertSame(200, $response->getStatusCode());
        try {
            $manager->refresh($session->refreshToken);
            $this->fail('The refresh token of the logged-out session must not refresh.');
        } catch (AuthenticationException) {
            $this->addToAssertionCount(1);
        }
        $this->assertInstanceOf(SessionResult::class, $manager->refresh($other->refreshToken));
    }

    public function testFailedLogoutLeavesTheRealSessionRefreshable(): void
    {
        $manager = new SessionManager(new InMemoryTokenStore(), $this->key);
        $session = $manager->create(Subject::from('42', 'user'));
        $app = $this->app($manager, 409);

        $response = $app->handle(
            $this->psr17->createServerRequest('POST', '/logout')
                ->withHeader('Authorization', 'Bearer ' . $session->accessToken),
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertInstanceOf(SessionResult::class, $manager->refresh($session->refreshToken));
    }

    /**
     * @param list<string> $calls
     */
    private function middleware(array &$calls): RevokeSessionOnSuccess
    {
        return new RevokeSessionOnSuccess(static function (string $reference) use (&$calls): void {
            $calls[] = $reference;
        });
    }

    private function requestWithReference(): ServerRequestInterface
    {
        return $this->psr17->createServerRequest('POST', '/logout')
            ->withAttribute(RequestAttribute::SESSION, self::REFERENCE);
    }

    private function handlerAnswering(int $status): FixedResponseHandler
    {
        return new FixedResponseHandler($this->psr17->createResponse($status));
    }

    private function app(SessionManager $manager, int $status): App
    {
        $routes = new Routes($this->psr17);
        $routes->middleware(new RequireSession(
            new SignedTokenAuthenticator($this->key),
            Credential::token(...),
            $this->psr17,
            $this->psr17,
        ));
        $routes->post(
            '/logout',
            fn (ServerRequestInterface $request): ResponseInterface => $this->psr17->createResponse($status),
            new RevokeSessionOnSuccess($manager->invalidateByReference(...)),
        );

        return new App(
            $routes,
            new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'),
            new AppConfig(requireSessionGuard: true),
        );
    }
}
