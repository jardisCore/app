<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Data\SessionDoor;
use JardisCore\App\Exception\SystemRunRefused;
use JardisCore\App\Middleware\RequireRight;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisCore\App\SignedInRun;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\Data\Subject;
use JardisSupport\Auth\Handler\Authorization\HasRight;
use JardisSupport\Auth\SessionManager;
use JardisSupport\Auth\SignedTokenAuthenticator;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SessionClaim;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration tests for SignedInRun through the real App pipeline: real RequireSession, SignedTokenAuthenticator,
 * RequireRight/HasRight and SessionManager (jardissupport/auth). The sign-in and switch-tenant doors are small
 * handlers that issue real sessions (rights by role and tenant); the system run must reach the target door only
 * with a token those doors issued, and it is judged by the target's own guards.
 */
final class SignedInRunTest extends TestCase
{
    private const SECRET = 'system-secret';

    private Psr17Factory $psr17;
    private string $key;
    private SessionManager $manager;
    /** @var list<ServerRequestInterface> */
    private array $targetCalls = [];
    /** @var list<string> */
    private array $switchBodies = [];
    private int $signInCalls = 0;
    /** sign-in answer shape: 'plain' | 'nested' | 'none' | 'two' | 'twice' */
    private string $signInShape = 'plain';
    /** @var array<string, list<string>> tenant => rights of the system user */
    private array $grants = ['acme' => ['sales.order:read']];
    private ?int $switchFails = null;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->key = random_bytes(32);
        $this->manager = new SessionManager(new InMemoryTokenStore(), $this->key);
        $this->targetCalls = [];
        $this->switchBodies = [];
        $this->signInCalls = 0;
        $this->signInShape = 'plain';
        $this->grants = ['acme' => ['sales.order:read']];
        $this->switchFails = null;
    }

    public function testSystemUserWithTheRightReachesTheTargetInItsTenant(): void
    {
        $response = $this->systemRun()->handle('acme', $this->credentials(), 'GET', '/orders');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $this->targetCalls);
        $this->assertSame('acme', $this->targetCalls[0]->getAttribute(RequestAttribute::TENANT));
        $this->assertSame('user:sys', $this->targetCalls[0]->getAttribute(RequestAttribute::SUBJECT));
        $this->assertSame(['{"tenantSlug":"acme"}'], $this->switchBodies);
    }

    public function testTargetMethodPathAndBodyArriveAtTheTargetDoor(): void
    {
        $response = $this->systemRun()->handle('acme', $this->credentials(), 'POST', '/orders/import?dry=1', '{"a":1}');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"a":1}', (string) $this->targetCalls[0]->getBody());
        $this->assertSame('dry=1', $this->targetCalls[0]->getUri()->getQuery());
    }

    public function testRoleWithoutTheRightGetsTheTarget403UnchangedAndTheHandlerIsNotCalled(): void
    {
        $this->grants = ['acme' => ['sales.invoice:read']];

        $response = $this->systemRun()->handle('acme', $this->credentials(), 'GET', '/orders');

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            '{"status":403,"data":{},"errors":{"message":"Right \"sales.order:read\" is missing.",'
            . '"messageKey":"access.rightMissing","context":{"right":"sales.order:read"}},"meta":{}}',
            (string) $response->getBody(),
        );
        $this->assertSame([], $this->targetCalls);
    }

    public function testTargetDoorAnswerIsReturnedUnchangedAlso404(): void
    {
        $response = $this->systemRun()->handle('acme', $this->credentials(), 'GET', '/nowhere');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([], $this->targetCalls);
    }

    public function testEmptyTenantIsRefusedBeforeAnyRequest(): void
    {
        try {
            $this->systemRun()->handle('', $this->credentials(), 'GET', '/orders');
            $this->fail('An empty tenant must be refused.');
        } catch (SystemRunRefused $refused) {
            $this->assertNull($refused->status);
        }

        $this->assertSame(0, $this->signInCalls);
        $this->assertSame([], $this->switchBodies);
        $this->assertSame([], $this->targetCalls);
    }

    public function testSignInThatAnswers401IsRefusedWithItsStatus(): void
    {
        try {
            $this->systemRun()->handle('acme', '{"secret":"wrong"}', 'GET', '/orders');
            $this->fail('A failed sign-in must be refused.');
        } catch (SystemRunRefused $refused) {
            $this->assertSame(401, $refused->status);
        }

        $this->assertSame([], $this->switchBodies, 'no switch after a failed sign-in');
        $this->assertSame([], $this->targetCalls);
    }

    public function testAnswerWithoutAnyAcceptedTokenIsRefused(): void
    {
        $this->signInShape = 'none';

        $this->assertRefused(200);
        $this->assertSame([], $this->switchBodies);
        $this->assertSame([], $this->targetCalls);
    }

    public function testAnswerWithTwoDifferentAcceptedTokensIsRefused(): void
    {
        $this->signInShape = 'two';

        $this->assertRefused(200);
        $this->assertSame([], $this->switchBodies);
        $this->assertSame([], $this->targetCalls);
    }

    public function testTheSameTokenFoundTwiceCountsOnce(): void
    {
        $this->signInShape = 'twice';

        $response = $this->systemRun()->handle('acme', $this->credentials(), 'GET', '/orders');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testTokenIsFoundInANestedDataValue(): void
    {
        $this->signInShape = 'nested';

        $response = $this->systemRun()->handle('acme', $this->credentials(), 'GET', '/orders');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSwitchThatAnswers422IsRefusedWithItsStatus(): void
    {
        $this->grants = [];

        $this->assertRefused(422);
        $this->assertSame(['{"tenantSlug":"acme"}'], $this->switchBodies);
        $this->assertSame([], $this->targetCalls);
    }

    public function testSwitchThatAnswers5xxIsRefusedWithItsStatus(): void
    {
        $this->switchFails = 503;

        $this->assertRefused(503);
        $this->assertSame([], $this->targetCalls);
    }

    private function assertRefused(int $status): void
    {
        try {
            $this->systemRun()->handle('acme', $this->credentials(), 'GET', '/orders');
            $this->fail('The run must be refused.');
        } catch (SystemRunRefused $refused) {
            $this->assertSame($status, $refused->status);
            $this->assertStringNotContainsString('eyJ', $refused->getMessage(), 'no token in the message');
        }
    }

    private function credentials(): string
    {
        return '{"secret":"' . self::SECRET . '"}';
    }

    private function systemRun(): SignedInRun
    {
        $authenticator = new SignedTokenAuthenticator($this->key);

        return new SignedInRun(
            $this->app($authenticator),
            $authenticator,
            Credential::token(...),
            new SessionDoor('POST', '/sign-in'),
            new SessionDoor('POST', '/switch-tenant'),
            'tenantSlug',
        );
    }

    private function app(SignedTokenAuthenticator $authenticator): App
    {
        $routes = new Routes($this->psr17);
        $routes->middleware(new RequireSession($authenticator, Credential::token(...), $this->psr17, $this->psr17));
        $routes->anonymous()->post('/sign-in', $this->signIn(...));
        $routes->post('/switch-tenant', $this->switchTenant(...));

        $hasRight = (new HasRight())->__invoke(...);
        $target = function (ServerRequestInterface $request): ResponseInterface {
            $this->targetCalls[] = $request;

            return $this->psr17->createResponse($request->getMethod() === 'POST' ? 201 : 200);
        };
        $routes->get('/orders', $target, new RequireRight($this->psr17, $this->psr17, 'sales.order:read', $hasRight));
        $routes->post(
            '/orders/import',
            $target,
            new RequireRight($this->psr17, $this->psr17, 'sales.order:read', $hasRight),
        );

        return new App(
            $routes,
            new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'),
            new AppConfig(requireSessionGuard: true),
        );
    }

    private function signIn(ServerRequestInterface $request): ResponseInterface
    {
        ++$this->signInCalls;
        $given = json_decode((string) $request->getBody(), true);
        if (!is_array($given) || ($given['secret'] ?? null) !== self::SECRET) {
            return $this->envelope(401, ['message' => 'Invalid credentials.'], []);
        }

        $session = $this->manager->create(Subject::from('sys'));
        $other = $this->manager->create(Subject::from('other'));
        $data = match ($this->signInShape) {
            'nested' => ['session' => ['tokens' => ['access' => $session->accessToken]], 'note' => 'ok'],
            'none' => ['refreshToken' => $session->refreshToken, 'note' => 'ok'],
            'two' => ['accessToken' => $session->accessToken, 'again' => $other->accessToken],
            'twice' => ['accessToken' => $session->accessToken, 'copy' => $session->accessToken],
            default => ['accessToken' => $session->accessToken, 'refreshToken' => $session->refreshToken],
        };

        return $this->envelope(200, [], $data);
    }

    private function switchTenant(ServerRequestInterface $request): ResponseInterface
    {
        $body = (string) $request->getBody();
        $this->switchBodies[] = $body;
        if ($this->switchFails !== null) {
            return $this->envelope($this->switchFails, ['message' => 'Unavailable.'], []);
        }

        $given = json_decode($body, true);
        $tenant = is_array($given) && is_string($given['tenantSlug'] ?? null) ? $given['tenantSlug'] : '';
        if (!isset($this->grants[$tenant])) {
            return $this->envelope(422, ['message' => 'No membership.'], []);
        }

        $session = $this->manager->create(
            Subject::parse((string) $request->getAttribute(RequestAttribute::SUBJECT)),
            [SessionClaim::TENANT => $tenant, SessionClaim::RIGHTS => $this->grants[$tenant]],
        );

        return $this->envelope(200, [], [
            'accessToken' => $session->accessToken,
            'refreshToken' => $session->refreshToken,
        ]);
    }

    /**
     * @param array<string, mixed> $errors
     * @param array<string, mixed> $data
     */
    private function envelope(int $status, array $errors, array $data): ResponseInterface
    {
        return $this->psr17->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->psr17->createStream(json_encode([
                'status' => $status,
                'data' => (object) $data,
                'errors' => (object) $errors,
                'meta' => (object) [],
            ], JSON_THROW_ON_ERROR)));
    }
}
