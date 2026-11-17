<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Handler\Request\ReadRequestAttributeIntList;
use JardisCore\App\Middleware\ResolveGroups;
use JardisCore\App\Middleware\RequireSession;
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
 * ResolveGroups through the real App pipeline (Routes -> Router -> RequireSession -> ResolveGroups -> handler).
 * Only the AuthenticatorInterface and the GroupLookupInterface ports are faked.
 */
final class ResolveGroupsTest extends TestCase
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
            'no-tenant' => ['user:43', null, '43', 'ref-def'],
            'empty-tenant' => ['user:44', '', '44', 'ref-ghi'],
            'plain' => ['alice', 'acme'],
            'boom-subject' => ['user:boom', 'acme', 'boom', 'ref-boom'],
        ]);
    }

    private function app(FakeGroupLookup $lookup): App
    {
        $routes = new Routes($this->factory);
        $routes->middleware(new RequireSession(
            $this->authenticator,
            FakeAuthenticator::credentialFor(...),
            $this->factory,
            $this->factory,
        ));
        $routes->middleware(new ResolveGroups($lookup));

        $handler = function (ServerRequestInterface $request): ResponseInterface {
            $this->handled[] = $request;

            return $this->factory->createResponse(200);
        };
        $routes->get('/orders', $handler);
        $routes->get('/forge/{jardisGroups}/{jardisPersonalGroup}', $handler);
        $routes->anonymous()->get('/login', $handler);
        $routes->anonymous()->get('/login-forge/{jardisGroups}/{jardisPersonalGroup}', $handler);

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

    public function testValidSessionGivesTheHandlerGroupsAndPersonalGroupAsIntegers(): void
    {
        $lookup = new FakeGroupLookup([7, 12], 12);

        $response = $this->get($this->app($lookup), '/orders', 'full');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $this->handled);
        $this->assertSame([7, 12], (new ReadRequestAttributeIntList())($this->handled[0], RequestAttribute::GROUPS));
        $this->assertSame(12, $this->handled[0]->getAttribute(RequestAttribute::PERSONAL_GROUP));
        $this->assertSame([['42', 'acme']], $lookup->calls, 'asked exactly once, bare subject id and tenant');
    }

    public function testSessionWithoutTenantAsksWithNull(): void
    {
        $lookup = new FakeGroupLookup([5], 5);

        $this->get($this->app($lookup), '/orders', 'no-tenant');
        $this->get($this->app($lookup), '/orders', 'empty-tenant');

        $this->assertSame([['43', null], ['44', null]], $lookup->calls);
    }

    public function testSubjectWithoutAnyGroupCarriesNeitherAttribute(): void
    {
        $lookup = new FakeGroupLookup([], null);

        $this->get($this->app($lookup), '/orders', 'full');

        $this->assertCount(1, $this->handled);
        $this->assertArrayNotHasKey(RequestAttribute::GROUPS, $this->handled[0]->getAttributes());
        $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $this->handled[0]->getAttributes());
        $this->assertSame([], (new ReadRequestAttributeIntList())($this->handled[0], RequestAttribute::GROUPS));
        $this->assertCount(1, $lookup->calls);
    }

    public function testAnonymousRouteNeverAsksTheContractAndDropsForgedAttributes(): void
    {
        $lookup = new FakeGroupLookup([7], 7);
        $app = $this->app($lookup);

        $this->assertSame(200, $this->get($app, '/login')->getStatusCode());
        $this->assertSame(200, $this->get($app, '/login-forge/666/666')->getStatusCode());

        $this->assertCount(2, $this->handled);
        foreach ($this->handled as $request) {
            $this->assertArrayNotHasKey(RequestAttribute::GROUPS, $request->getAttributes());
            $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $request->getAttributes());
        }
        $this->assertSame([], $lookup->calls);
    }

    public function testSessionWithoutSubjectIdNeverAsksTheContractAndDropsForgedAttributes(): void
    {
        $lookup = new FakeGroupLookup([7], 7);

        $response = $this->get($this->app($lookup), '/forge/666/666', 'plain');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $this->handled);
        $this->assertArrayNotHasKey(RequestAttribute::GROUPS, $this->handled[0]->getAttributes());
        $this->assertArrayNotHasKey(RequestAttribute::PERSONAL_GROUP, $this->handled[0]->getAttributes());
        $this->assertSame([], $lookup->calls);
    }

    public function testClientValuesAreReplacedByTheResolvedGroups(): void
    {
        $lookup = new FakeGroupLookup([7], 7);

        $this->get($this->app($lookup), '/forge/666/666', 'full');

        $this->assertCount(1, $this->handled);
        $this->assertSame([7], $this->handled[0]->getAttribute(RequestAttribute::GROUPS));
        $this->assertSame(7, $this->handled[0]->getAttribute(RequestAttribute::PERSONAL_GROUP));
    }

    public function testGuestIsAnswered401AndTheContractIsNotAsked(): void
    {
        $lookup = new FakeGroupLookup([7], 7);

        $response = $this->get($this->app($lookup), '/orders');

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame([], $this->handled);
        $this->assertSame([], $lookup->calls);
    }

    public function testThrowingContractIsAnsweredByTheErrorPathWithFiveHundredNeverTwoHundred(): void
    {
        $lookup = new FakeGroupLookup([7], 7);

        $response = $this->get($this->app($lookup), '/orders', 'boom-subject');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('group store unreachable', (string) $response->getBody());
        $this->assertSame([], $this->handled, 'the handler is not reached');
        $this->assertCount(1, $lookup->calls);
    }
}
