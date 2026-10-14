<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use DateTimeImmutable;
use DateTimeZone;
use JardisAdapter\DbConnection\ConnectionPool;
use JardisAdapter\DbConnection\Factory\ConnectionFactory;
use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Middleware\MiddlewareFactory;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\SettableKernel;
use JardisSupport\Auth\Data\HashedToken;
use JardisSupport\Auth\Data\Token;
use JardisSupport\Auth\DbTokenStore;
use JardisSupport\Contract\Auth\RequestAttribute;
use JardisSupport\Contract\Auth\TokenType;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\DbConnection\DbConnectionInterface;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration test of the session flow against the real token table: the token store is NOT passed
 * to the MiddlewareFactory but resolved from the kernel's database connection, the table is created
 * from the DDL shipped with jardissupport/auth, and the requests run through the real App pipeline.
 * Nothing is faked.
 */
final class SessionDbTokenStoreTest extends TestCase
{
    private const SQLITE_DDL = __DIR__ . '/../../../vendor/jardissupport/auth/resources/sql/sqlite.sql';

    private Psr17Factory $psr17;
    private PDO $pdo;
    private DbConnectionInterface $connection;
    private DbTokenStore $store;
    private ?ServerRequestInterface $seen = null;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec((string) file_get_contents(self::SQLITE_DDL));
        $this->connection = (new ConnectionFactory())->fromPdo($this->pdo);
        $this->store = new DbTokenStore($this->connection);
        $this->seen = null;
    }

    public function testPdoKernelProtectsRoutesWithTheDatabaseTokenStore(): void
    {
        $token = $this->issueToken('user:42', ['tenant' => 'acme']);
        $app = $this->appFor(SettableKernel::with($this->pdo));

        $open = $app->handle($this->request('/open'));
        $withoutToken = $app->handle($this->request('/orders'));
        $withToken = $app->handle($this->request('/orders', $token->value));
        $withWrongToken = $app->handle($this->request('/orders', bin2hex(random_bytes(32))));

        $this->assertSame(200, $open->getStatusCode());
        $this->assertSame(401, $withoutToken->getStatusCode());
        $this->assertSame(200, $withToken->getStatusCode());
        $this->assertSame(401, $withWrongToken->getStatusCode());
    }

    public function testHandlerSeesSubjectTenantAndSessionOfTheStoredToken(): void
    {
        $token = $this->issueToken('user:42', ['tenant' => 'acme']);
        $app = $this->appFor(SettableKernel::with($this->pdo));

        $app->handle($this->request('/orders', $token->value));

        $this->assertNotNull($this->seen);
        $this->assertSame('user:42', $this->seen->getAttribute(RequestAttribute::SUBJECT));
        $this->assertSame('42', $this->seen->getAttribute(RequestAttribute::SUBJECT_ID));
        $this->assertSame('acme', $this->seen->getAttribute(RequestAttribute::TENANT));
        $this->assertSame(HashedToken::hashOf($token->value), $this->seen->getAttribute(RequestAttribute::SESSION));
    }

    public function testTokenWithoutTenantClaimLeavesTenantAttributeAbsent(): void
    {
        $token = $this->issueToken('user:42');
        $app = $this->appFor(SettableKernel::with($this->pdo));

        $response = $app->handle($this->request('/orders', $token->value));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($this->seen);
        $this->assertNull($this->seen->getAttribute(RequestAttribute::TENANT));
        $this->assertArrayNotHasKey(RequestAttribute::TENANT, $this->seen->getAttributes());
    }

    public function testRevokedTokenIsRejectedAfterItWasAccepted(): void
    {
        $token = $this->issueToken('user:42');
        $app = $this->appFor(SettableKernel::with($this->pdo));
        $before = $app->handle($this->request('/orders', $token->value));

        $this->store->revoke(HashedToken::hashOf($token->value));
        $after = $app->handle($this->request('/orders', $token->value));

        $this->assertSame(200, $before->getStatusCode());
        $this->assertSame(401, $after->getStatusCode());
    }

    public function testExpiredTokenIsRejected(): void
    {
        $utc = new DateTimeZone('UTC');
        $expired = new HashedToken(
            hash: HashedToken::hashOf('expired-plain-token'),
            type: TokenType::Access,
            subject: 'user:42',
            claims: [],
            expiresAt: new DateTimeImmutable('-1 hour', $utc),
            createdAt: new DateTimeImmutable('-2 hours', $utc),
        );
        $this->store->store($expired);
        $app = $this->appFor(SettableKernel::with($this->pdo));

        $response = $app->handle($this->request('/orders', 'expired-plain-token'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testPoolKernelProtectsRoutesWithTheDatabaseTokenStore(): void
    {
        $token = $this->issueToken('user:42', ['tenant' => 'acme']);
        $pool = new ConnectionPool($this->connection);
        $this->assertInstanceOf(ConnectionPoolInterface::class, $pool);
        $app = $this->appFor(SettableKernel::with($pool));

        $open = $app->handle($this->request('/open'));
        $withoutToken = $app->handle($this->request('/orders'));
        $withToken = $app->handle($this->request('/orders', $token->value));
        $withWrongToken = $app->handle($this->request('/orders', bin2hex(random_bytes(32))));

        $this->assertSame(200, $open->getStatusCode());
        $this->assertSame(401, $withoutToken->getStatusCode());
        $this->assertSame(200, $withToken->getStatusCode());
        $this->assertSame(401, $withWrongToken->getStatusCode());
        $this->assertSame('acme', $this->seen?->getAttribute(RequestAttribute::TENANT));
    }

    /**
     * Stores a token the way a login does: only the hash lands in the table.
     *
     * @param array<string, mixed> $claims
     */
    private function issueToken(string $subject, array $claims = []): Token
    {
        $token = Token::create(TokenType::Access)->forSubject($subject)->expiresIn(3600)->withClaims($claims);
        $this->store->store($token->hash());

        return $token;
    }

    private function appFor(DomainKernelInterface $kernel): App
    {
        $routes = new Routes($this->psr17);
        $routes->middleware((new MiddlewareFactory($kernel))('session'));
        $handler = function (ServerRequestInterface $request): ResponseInterface {
            $this->seen = $request;

            return $this->psr17->createResponse(200);
        };
        $routes->anonymous()->get('/open', $handler);
        $routes->get('/orders', $handler);

        return new App($routes, $kernel, new AppConfig());
    }

    private function request(string $path, ?string $bearer = null): ServerRequestInterface
    {
        $request = $this->psr17->createServerRequest('GET', $path);

        return $bearer === null ? $request : $request->withHeader('Authorization', 'Bearer ' . $bearer);
    }
}
