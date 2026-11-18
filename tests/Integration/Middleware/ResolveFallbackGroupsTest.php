<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Middleware\ResolveFallbackGroups;
use JardisCore\App\Middleware\ResolveGroups;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\FakeAuthenticator;
use JardisCore\App\Tests\Support\FakeGroupLookup;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Contract\Auth\RequestAttribute;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ResolveFallbackGroups through the real App pipeline (Routes -> Router -> RequireSession -> ResolveGroups ->
 * ResolveFallbackGroups -> handler). Only the AuthenticatorInterface and the GroupLookupInterface ports are faked.
 */
final class ResolveFallbackGroupsTest extends TestCase
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
            'full' => ['user:42', 'acme', '42', 'ref-abc'],
            'empty-tenant' => ['user:44', '', '44', 'ref-ghi'],
            'plain' => ['alice', 'acme'],
        ]);
    }

    /**
     * @param list<int> $kinds
     */
    private function app(FakeGroupLookup $lookup, array $kinds, bool $withResolveGroups = true): App
    {
        $routes = new Routes($this->factory);
        $routes->middleware(new RequireSession(
            $this->authenticator,
            FakeAuthenticator::credentialFor(...),
            $this->factory,
            $this->factory,
        ));
        if ($withResolveGroups) {
            $routes->middleware(new ResolveGroups($lookup));
        }
        $routes->middleware(new ResolveFallbackGroups($lookup, $kinds));

        $handler = function (ServerRequestInterface $request): ResponseInterface {
            $this->handled[] = $request;

            return $this->factory->createResponse(200);
        };
        $routes->get('/orders', $handler);
        $routes->get('/forge/{jardisFallbackGroups}', $handler);
        $routes->anonymous()->get('/login', $handler);

        return new App($routes, new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'), new AppConfig());
    }

    private function get(App $app, string $path, ?string $token = null): ResponseInterface
    {
        $request = $this->factory->createServerRequest('GET', $path);
        if ($token !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $token);
        }

        return $app->handle($request);
    }

    private function handledRequest(): ServerRequestInterface
    {
        $this->assertCount(1, $this->handled);

        return $this->handled[0];
    }

    private function fallbackOfHandled(): mixed
    {
        $this->assertCount(1, $this->handled);

        return $this->handled[0]->getAttribute(RequestAttribute::FALLBACK_GROUPS);
    }

    public function testPersonalGroupPresentIsNotAskedAndTheAttributeIsAbsent(): void
    {
        $lookup = new FakeGroupLookup([7, 12], 12, fallbackGroups: [3 => 41]);

        $response = $this->get($this->app($lookup, [3]), '/orders', 'full');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($this->fallbackOfHandled());
        $this->assertSame([], $lookup->fallbackCalls);
    }

    public function testAnonymousRequestWithoutSubjectIsAskedAndGetsTheFallbackGroups(): void
    {
        $lookup = new FakeGroupLookup([7], 7, fallbackGroups: [3 => 41, 4 => 42]);

        $response = $this->get($this->app($lookup, [3, 4]), '/login');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([3 => 41, 4 => 42], $this->fallbackOfHandled());
        $this->assertSame([[3, null], [4, null]], $lookup->fallbackCalls);
        $this->assertSame([], $lookup->calls, 'no subject: groupsOf is not asked');
    }

    public function testPersonWithoutPersonalGroupIsAskedPerKindWithTheTenant(): void
    {
        $lookup = new FakeGroupLookup([7], null, fallbackGroups: [3 => 41, 5 => 43]);

        $this->get($this->app($lookup, [3, 5]), '/orders', 'full');

        $this->assertSame([3 => 41, 5 => 43], $this->fallbackOfHandled());
        $this->assertSame([[3, 'acme'], [5, 'acme']], $lookup->fallbackCalls);
    }

    public function testEmptyTenantIsAskedAsNull(): void
    {
        $lookup = new FakeGroupLookup([], null, fallbackGroups: [3 => 41]);

        $this->get($this->app($lookup, [3]), '/orders', 'empty-tenant');

        $this->assertSame([3 => 41], $this->fallbackOfHandled());
        $this->assertSame([[3, null]], $lookup->fallbackCalls);
    }

    public function testOnlyNullAnswersLeaveTheAttributeAbsent(): void
    {
        $lookup = new FakeGroupLookup([], null);

        $this->get($this->app($lookup, [3, 4]), '/orders', 'full');

        $this->assertCount(1, $this->handled);
        $this->assertArrayNotHasKey(RequestAttribute::FALLBACK_GROUPS, $this->handled[0]->getAttributes());
        $this->assertCount(2, $lookup->fallbackCalls, 'every kind was asked');
    }

    public function testMixedAnswersKeepOnlyTheKindsWithAFallbackGroup(): void
    {
        $lookup = new FakeGroupLookup([], null, fallbackGroups: [3 => 41, 4 => null, 5 => 43]);

        $this->get($this->app($lookup, [3, 4, 5]), '/orders', 'full');

        $this->assertSame([3 => 41, 5 => 43], $this->fallbackOfHandled());
    }

    public function testNoKindsMeansNoQuestionAndNoAttribute(): void
    {
        $lookup = new FakeGroupLookup([], null, fallbackGroups: [3 => 41]);

        $this->get($this->app($lookup, []), '/orders', 'full');

        $this->assertCount(1, $this->handled);
        $this->assertArrayNotHasKey(RequestAttribute::FALLBACK_GROUPS, $this->handled[0]->getAttributes());
        $this->assertSame([], $lookup->fallbackCalls);
    }

    public function testClientValueIsReplacedByTheResolvedFallbackGroups(): void
    {
        $lookup = new FakeGroupLookup([], null, fallbackGroups: [3 => 41]);

        $this->get($this->app($lookup, [3]), '/forge/666', 'full');

        $this->assertSame([3 => 41], $this->fallbackOfHandled());
    }

    public function testClientValueIsRemovedWhenThePersonHasAPersonalGroup(): void
    {
        $lookup = new FakeGroupLookup([12], 12, fallbackGroups: [3 => 41]);

        $this->get($this->app($lookup, [3]), '/forge/666', 'full');

        $this->assertNull($this->fallbackOfHandled());
    }

    public function testBehindResolveGroupsAPersonWithAPersonalGroupGetsNoFallbackAndOneWithoutGetsIt(): void
    {
        $withPersonal = new FakeGroupLookup([12], 12, fallbackGroups: [3 => 41]);
        $this->get($this->app($withPersonal, [3]), '/orders', 'full');
        $first = $this->handledRequest();
        $this->assertSame(12, $first->getAttribute(RequestAttribute::PERSONAL_GROUP));
        $this->assertArrayNotHasKey(RequestAttribute::FALLBACK_GROUPS, $first->getAttributes());

        $this->handled = [];
        $without = new FakeGroupLookup([7], null, fallbackGroups: [3 => 41]);
        $this->get($this->app($without, [3]), '/orders', 'full');
        $second = $this->handledRequest();
        $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $second->getAttributes());
        $this->assertSame([3 => 41], $second->getAttribute(RequestAttribute::FALLBACK_GROUPS));
    }

    public function testThrowingContractIsAnsweredByTheErrorPathWithFiveHundredNeverTwoHundred(): void
    {
        $lookup = new FakeGroupLookup([], null, fallbackThrows: true);

        $response = $this->get($this->app($lookup, [3]), '/orders', 'full');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('fallback store unreachable', (string) $response->getBody());
        $this->assertSame([], $this->handled, 'the handler is not reached');
        $this->assertCount(1, $lookup->fallbackCalls);
    }
}
