<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Data\ShareTargetSource;
use JardisCore\App\Middleware\RequireRight;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Middleware\ResolveShareTargets;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\FakeGroupLookup;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisCore\Kernel\DomainKernel;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\Data\Subject;
use JardisSupport\Auth\Handler\Authorization\HasRight;
use JardisSupport\Auth\SessionManager;
use JardisSupport\Auth\SignedTokenAuthenticator;
use JardisSupport\Contract\Auth\GroupLookupInterface;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\SessionClaim;
use JardisSupport\Contract\Auth\SubjectGroupsInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * ResolveShareTargets through the real App pipeline (Routes -> Router -> RequireSession -> RequireRight ->
 * ResolveShareTargets -> handler), for both sources: header (create) and body fields (share). Real session
 * token and real HasRight; only the GroupLookupInterface port is faked.
 */
final class ResolveShareTargetsTest extends TestCase
{
    private const RIGHT = 'sales.order:create';
    private const TENANT = 'acme';

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

    // ---- header source (create with "share with") --------------------------------------------------------

    public function testHeaderPairsBecomeGroupToWordPairsForGroupsOfTheTenant(): void
    {
        $lookup = $this->lookup();

        $response = $this->post(
            $this->app($lookup, ShareTargetSource::Header),
            '/orders',
            headers: ['X-Jardis-Share-With' => ' 12=view , 15=edit,7=all'],
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([12 => 'view', 15 => 'edit', 7 => 'all'], $this->targets());
        $this->assertSame([[[12, 15, 7], self::TENANT]], $lookup->existingCalls);
        $this->assertSame([], $lookup->personalCalls, 'the header names no person');
    }

    public function testHeaderEntryWithoutEqualsSignCarriesTheEmptyWord(): void
    {
        $this->post($this->app($this->lookup(), ShareTargetSource::Header), '/orders', headers: ['X-Jardis-Share-With' => '12']);

        $this->assertSame([12 => ''], $this->targets());
    }

    public function testHeaderWordIsPassedThroughUnchangedNeverChecked(): void
    {
        $this->post(
            $this->app($this->lookup(), ShareTargetSource::Header),
            '/orders',
            headers: ['X-Jardis-Share-With' => '12=Superb, 15=a=b'],
        );

        $this->assertSame([12 => 'Superb', 15 => 'a=b'], $this->targets());
    }

    public function testHeaderGroupThatIsNoWholeNumberIsNotResolvableAndTheContractIsNotAsked(): void
    {
        foreach (['x=view', '12=view, abc=edit', '1.5=view', '-3=view', '=view', '012x=view'] as $value) {
            $this->handled = [];
            $lookup = $this->lookup();

            $response = $this->post($this->app($lookup, ShareTargetSource::Header), '/orders', headers: ['X-Jardis-Share-With' => $value]);

            $this->assertSame(200, $response->getStatusCode(), $value);
            $this->assertFalse($this->targets(), $value);
            $this->assertSame([], $lookup->existingCalls, $value);
        }
    }

    public function testHeaderAbsentOrBlankLeavesTheAttributeOutAndNeverAsksTheContract(): void
    {
        $lookup = $this->lookup();
        $app = $this->app($lookup, ShareTargetSource::Header);

        $this->post($app, '/orders');
        $this->post($app, '/orders', headers: ['X-Jardis-Share-With' => '  ']);
        $this->post($app, '/orders', headers: ['X-Jardis-Share-With' => ' , ']);

        $this->assertCount(3, $this->handled);
        foreach ($this->handled as $request) {
            $this->assertArrayNotHasKey(RequestAttribute::SHARE_TARGETS, $request->getAttributes());
        }
        $this->assertSame([], $lookup->existingCalls);
        $this->assertSame([], $lookup->personalCalls);
    }

    public function testHeaderGroupOfAnotherTenantIsNotResolvableAndTheHandlerStillRuns(): void
    {
        $lookup = $this->lookup(onlyTenant: 'other-tenant');

        $response = $this->post($this->app($lookup, ShareTargetSource::Header), '/orders', headers: ['X-Jardis-Share-With' => '12=view']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($this->targets());
    }

    public function testHeaderUnknownGroupMakesTheWholeSetNotResolvable(): void
    {
        $response = $this->post(
            $this->app($this->lookup(), ShareTargetSource::Header),
            '/orders',
            headers: ['X-Jardis-Share-With' => '12=view, 999=edit'],
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($this->targets());
    }

    public function testTenantIsReadFromTheSessionAndEmptyMeansNull(): void
    {
        $lookup = $this->lookup();

        $this->post(
            $this->app($lookup, ShareTargetSource::Header, tenant: ''),
            '/orders',
            headers: ['X-Jardis-Share-With' => '12=view'],
        );

        $this->assertSame([[[12], null]], $lookup->existingCalls);
    }

    public function testClientValueOfTheAttributeIsRemovedWhenNothingIsNamed(): void
    {
        $lookup = $this->lookup();

        $this->post($this->app($lookup, ShareTargetSource::Header), '/forge/66');

        $this->assertCount(1, $this->handled);
        $this->assertArrayNotHasKey(RequestAttribute::SHARE_TARGETS, $this->handled[0]->getAttributes());
        $this->assertSame([], $lookup->existingCalls);
    }

    public function testClientValueOfTheAttributeIsReplacedByTheResolvedTargets(): void
    {
        $this->post(
            $this->app($this->lookup(), ShareTargetSource::Header),
            '/forge/66',
            headers: ['X-Jardis-Share-With' => '12=view'],
        );

        $this->assertSame([12 => 'view'], $this->targets());
    }

    // ---- body source (share, also with a person) ---------------------------------------------------------

    public function testBodyGroupsAndPersonResolveToTheirGroupsWithTheOneLevel(): void
    {
        $lookup = $this->lookup(personalGroups: ['42' => 100]);

        $this->post(
            $this->app($lookup, ShareTargetSource::Body),
            '/orders',
            '{"groups":[12,15],"person":"42","level":"edit"}',
        );

        $this->assertSame([12 => 'edit', 15 => 'edit', 100 => 'edit'], $this->targets());
        $this->assertSame([[[12, 15], self::TENANT]], $lookup->existingCalls);
        $this->assertSame([['42', self::TENANT]], $lookup->personalCalls);
    }

    public function testBodyPersonAloneResolvesToHerPersonalGroupOnly(): void
    {
        $lookup = $this->lookup(personalGroups: ['42' => 100]);

        $this->post($this->app($lookup, ShareTargetSource::Body), '/orders', '{"person":"42","level":"view"}');

        $this->assertSame([100 => 'view'], $this->targets());
        $this->assertSame([], $lookup->existingCalls, 'no group named, nothing to check');
    }

    public function testBodyGroupsAloneAreTheSameNumbers(): void
    {
        $this->post($this->app($this->lookup(), ShareTargetSource::Body), '/orders', '{"groups":[7],"level":"all"}');

        $this->assertSame([7 => 'all'], $this->targets());
    }

    public function testBodyLevelMissingOrNotAStringIsTheEmptyWord(): void
    {
        $app = $this->app($this->lookup(), ShareTargetSource::Body);

        $this->post($app, '/orders', '{"groups":[7]}');
        $this->assertSame([7 => ''], $this->targets());

        $this->handled = [];
        $this->post($app, '/orders', '{"groups":[7],"level":3}');
        $this->assertSame([7 => ''], $this->targets());
    }

    public function testBodyWordIsPassedThroughUnchangedNeverChecked(): void
    {
        $this->post($this->app($this->lookup(), ShareTargetSource::Body), '/orders', '{"groups":[7],"level":"Nonsense"}');

        $this->assertSame([7 => 'Nonsense'], $this->targets());
    }

    public function testBodyWithoutGroupsAndPersonLeavesTheAttributeOut(): void
    {
        $lookup = $this->lookup();
        $app = $this->app($lookup, ShareTargetSource::Body);

        foreach (['{}', '{"level":"view"}', '{"groups":[]}', '{"groups":null,"person":null}', '[1,2]'] as $body) {
            $this->handled = [];
            $this->post($app, '/orders', $body);

            $this->assertCount(1, $this->handled, $body);
            $this->assertArrayNotHasKey(RequestAttribute::SHARE_TARGETS, $this->lastRequest()->getAttributes(), $body);
        }
        $this->assertSame([], $lookup->existingCalls);
        $this->assertSame([], $lookup->personalCalls);
    }

    public function testEmptyOrBrokenBodyIsCaughtTheAttributeIsAbsentAndTheHandlerRuns(): void
    {
        $lookup = $this->lookup();
        $app = $this->app($lookup, ShareTargetSource::Body);

        foreach (['', '{broken', '"text"', '17'] as $body) {
            $this->handled = [];
            $response = $this->post($app, '/orders', $body);

            $this->assertSame(200, $response->getStatusCode(), $body);
            $this->assertCount(1, $this->handled, $body);
            $this->assertArrayNotHasKey(RequestAttribute::SHARE_TARGETS, $this->lastRequest()->getAttributes(), $body);
        }
        $this->assertSame([], $lookup->existingCalls);
    }

    public function testBodyGroupsThatAreNotAListOfIntegersOrPersonThatIsNoStringAreNotResolvable(): void
    {
        $lookup = $this->lookup();
        $app = $this->app($lookup, ShareTargetSource::Body);

        foreach ([
            '{"groups":"12"}',
            '{"groups":["12"]}',
            '{"groups":[1.5]}',
            '{"groups":{"a":1}}',
            '{"groups":[12,null]}',
            '{"person":42}',
            '{"groups":[12],"person":["42"]}',
        ] as $body) {
            $this->handled = [];
            $this->post($app, '/orders', $body);

            $this->assertFalse($this->targets(), $body);
        }
        $this->assertSame([], $lookup->existingCalls);
        $this->assertSame([], $lookup->personalCalls);
    }

    public function testBodyPersonWithoutPersonalGroupOrOfAnotherTenantIsNotResolvable(): void
    {
        $app = $this->app($this->lookup(personalGroups: ['42' => 100]), ShareTargetSource::Body);
        $this->post($app, '/orders', '{"person":"stranger","level":"view"}');
        $this->assertFalse($this->targets());

        $this->handled = [];
        $app = $this->app($this->lookup(personalGroups: ['42' => 100], onlyTenant: 'other-tenant'), ShareTargetSource::Body);
        $this->post($app, '/orders', '{"person":"42","level":"view"}');
        $this->assertFalse($this->targets());
    }

    public function testBodyGroupOfAnotherTenantIsNotResolvableEvenWithAValidPerson(): void
    {
        $lookup = $this->lookup(personalGroups: ['42' => 100]);

        $this->post($this->app($lookup, ShareTargetSource::Body), '/orders', '{"groups":[12,999],"person":"42","level":"view"}');

        $this->assertFalse($this->targets());
    }

    public function testBodyCanBeReadAgainByTheHandlerAfterwards(): void
    {
        $this->post($this->app($this->lookup(), ShareTargetSource::Body), '/orders', '{"groups":[7],"level":"view"}');

        $this->assertSame('{"groups":[7],"level":"view"}', (string) $this->handled[0]->getBody());
    }

    // ---- both sources ------------------------------------------------------------------------------------

    public function testRightMissingIsAnsweredByRequireRightAndTheLookupIsNotAsked(): void
    {
        $lookup = $this->lookup();

        $response = $this->post(
            $this->app($lookup, ShareTargetSource::Header),
            '/orders',
            headers: ['X-Jardis-Share-With' => '12=view'],
            rights: ['sales.invoice:read'],
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([], $this->handled);
        $this->assertSame([], $lookup->existingCalls);
    }

    public function testThrowingLookupIsAnsweredByTheErrorPathWithFiveHundred(): void
    {
        $lookup = new class () implements GroupLookupInterface {
            public function groupsOf(string $subjectId, ?string $tenant): SubjectGroupsInterface
            {
                throw new RuntimeException('group store unreachable');
            }

            public function personalGroupOf(string $subjectId, ?string $tenant): ?int
            {
                throw new RuntimeException('group store unreachable');
            }

            public function existingGroups(array $groups, ?string $tenant): array
            {
                throw new RuntimeException('group store unreachable');
            }

            public function fallbackGroupOf(int $kind, ?string $tenant): ?int
            {
                throw new RuntimeException('group store unreachable');
            }
        };

        $response = $this->post(
            $this->app($lookup, ShareTargetSource::Header),
            '/orders',
            headers: ['X-Jardis-Share-With' => '12=view'],
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('group store unreachable', (string) $response->getBody());
        $this->assertSame([], $this->handled);
    }

    // ---- helpers -----------------------------------------------------------------------------------------

    /**
     * @param array<array-key, int>|null $personalGroups
     */
    private function lookup(?array $personalGroups = null, ?string $onlyTenant = self::TENANT): FakeGroupLookup
    {
        return new FakeGroupLookup([], null, [7, 12, 15, 100], $personalGroups ?? [], $onlyTenant);
    }

    private function app(GroupLookupInterface $lookup, ShareTargetSource $source, string $tenant = self::TENANT): App
    {
        $this->tenant = $tenant;

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
        foreach (['/orders', '/forge/{jardisShareTargets}'] as $path) {
            $routes->post(
                $path,
                $handler,
                new RequireRight($this->psr17, $this->psr17, self::RIGHT, $hasRight),
                new ResolveShareTargets($lookup, $source),
            );
        }

        return new App(
            $routes,
            new DomainKernel(projectRoot: '/tmp/jardiscore-app-test'),
            new AppConfig(requireSessionGuard: true),
        );
    }

    private string $tenant = self::TENANT;

    /**
     * @param array<string, string> $headers
     * @param list<string> $rights
     */
    private function post(
        App $app,
        string $path,
        string $body = '',
        array $headers = [],
        array $rights = [self::RIGHT],
    ): ResponseInterface {
        $claims = [SessionClaim::RIGHTS => $rights];
        if ($this->tenant !== '') {
            $claims[SessionClaim::TENANT] = $this->tenant;
        }
        $token = (new SessionManager(new InMemoryTokenStore(), $this->key))
            ->create(Subject::from('42', 'user'), $claims)
            ->accessToken;

        $request = $this->psr17->createServerRequest('POST', $path)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withBody($this->psr17->createStream($body));
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $app->handle($request);
    }

    /**
     * @return array<int, string>|false|null the attribute as the handler saw it; `null` = absent
     */
    private function targets(): array|false|null
    {
        return $this->lastRequest()->getAttribute(RequestAttribute::SHARE_TARGETS);
    }

    private function lastRequest(): ServerRequestInterface
    {
        $last = end($this->handled);
        $this->assertInstanceOf(ServerRequestInterface::class, $last, 'the handler did not run');

        return $last;
    }
}
