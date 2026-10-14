<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Middleware;

use InvalidArgumentException;
use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Contract\SessionGuardInterface;
use JardisCore\App\Handler\Middleware\ResolveTokenStore;
use JardisCore\App\Middleware\MiddlewareFactory;
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisCore\App\Tests\Support\AbstractMiddleware;
use JardisCore\App\Tests\Support\InMemoryTokenStore;
use JardisCore\App\Tests\Support\KernelMiddleware;
use JardisCore\App\Tests\Support\PlainMiddleware;
use JardisCore\App\Tests\Support\SettableKernel;
use JardisSupport\Auth\Data\Token;
use JardisSupport\Auth\DbTokenStore;
use JardisSupport\Contract\Auth\TokenType;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\DbConnection\DbConnectionInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use stdClass;

/**
 * Integration tests for MiddlewareFactory: name to PSR-15 middleware, through the real App pipeline.
 * Only the TokenStoreInterface and the database ports are faked.
 */
final class MiddlewareFactoryTest extends TestCase
{
    public function testSessionWithPassedStoreBuildsRequireSession(): void
    {
        $factory = new MiddlewareFactory(SettableKernel::with(), tokenStore: new InMemoryTokenStore());

        $middleware = $factory('session');

        $this->assertInstanceOf(RequireSession::class, $middleware);
        $this->assertInstanceOf(SessionGuardInterface::class, $middleware);
    }

    public function testPassedStoreWinsOverThePool(): void
    {
        $pool = $this->createMock(ConnectionPoolInterface::class);
        $pool->expects($this->never())->method($this->anything());

        $factory = new MiddlewareFactory(SettableKernel::with($pool), tokenStore: new InMemoryTokenStore());

        $this->assertInstanceOf(RequireSession::class, $factory('session'));
    }

    public function testPoolWithoutStoreResolvesDbTokenStoreWithoutIo(): void
    {
        $writer = $this->createMock(DbConnectionInterface::class);
        $writer->expects($this->never())->method($this->anything());
        $pool = $this->createMock(ConnectionPoolInterface::class);
        $pool->expects($this->once())->method('getWriter')->willReturn($writer);

        $store = (new ResolveTokenStore(SettableKernel::with($pool)))();

        $this->assertInstanceOf(DbTokenStore::class, $store);
    }

    public function testPoolWithoutStoreBuildsSessionMiddlewareWithoutIo(): void
    {
        $writer = $this->createMock(DbConnectionInterface::class);
        $writer->expects($this->never())->method($this->anything());
        $pool = $this->createMock(ConnectionPoolInterface::class);
        $pool->method('getWriter')->willReturn($writer);

        $factory = new MiddlewareFactory(SettableKernel::with($pool));

        $this->assertInstanceOf(RequireSession::class, $factory('session'));
    }

    public function testPdoWithoutStoreResolvesDbTokenStore(): void
    {
        $store = (new ResolveTokenStore(SettableKernel::with(new \PDO('sqlite::memory:'))))();

        $this->assertInstanceOf(DbTokenStore::class, $store);
    }

    public function testNeitherStoreNorDatabaseThrowsOnInvokeNotOnConstruction(): void
    {
        $factory = new MiddlewareFactory(SettableKernel::with());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('database connection');

        $factory('session');
    }

    public function testProjectMiddlewareWithKernelConstructorReceivesTheKernel(): void
    {
        $kernel = SettableKernel::with();
        $factory = new MiddlewareFactory($kernel);

        $middleware = $factory(KernelMiddleware::class);

        $this->assertInstanceOf(KernelMiddleware::class, $middleware);
        $this->assertSame($kernel, $middleware->kernel);
    }

    public function testProjectMiddlewareWithoutConstructor(): void
    {
        $factory = new MiddlewareFactory(SettableKernel::with());

        $this->assertInstanceOf(PlainMiddleware::class, $factory(PlainMiddleware::class));
    }

    public function testAbstractClassIsRejected(): void
    {
        $factory = new MiddlewareFactory(SettableKernel::with());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(AbstractMiddleware::class);

        $factory(AbstractMiddleware::class);
    }

    public function testNonMiddlewareClassIsRejected(): void
    {
        $factory = new MiddlewareFactory(SettableKernel::with());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(stdClass::class);

        $factory(stdClass::class);
    }

    public function testUnknownClassIsRejected(): void
    {
        $factory = new MiddlewareFactory(SettableKernel::with());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Does\\Not\\Exist');

        $factory('Does\\Not\\Exist');
    }

    public function testUnknownStandardNameIsRejectedWithHint(): void
    {
        $factory = new MiddlewareFactory(SettableKernel::with());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"session"');

        $factory('nonsense');
    }

    public function testSessionMiddlewareThroughApp(): void
    {
        $psr17 = new Psr17Factory();
        $store = new InMemoryTokenStore();
        $token = Token::create(TokenType::Access)->forSubject('alice')->withClaims(['tenant' => 'acme']);
        $store->store($token->hash());

        $kernel = SettableKernel::with();
        $routes = new Routes($psr17);
        $routes->middleware((new MiddlewareFactory($kernel, tokenStore: $store))('session'));
        $handler = function (ServerRequestInterface $request) use ($psr17): ResponseInterface {
            return $psr17->createResponse(200);
        };
        $routes->anonymous()->get('/open', $handler);
        $routes->get('/orders', $handler);
        $app = new App($routes, $kernel, new AppConfig());

        $open = $app->handle($psr17->createServerRequest('GET', '/open'));
        $denied = $app->handle($psr17->createServerRequest('GET', '/orders'));
        $granted = $app->handle(
            $psr17->createServerRequest('GET', '/orders')->withHeader('Authorization', 'Bearer ' . $token->value)
        );

        $this->assertSame(200, $open->getStatusCode());
        $this->assertSame(401, $denied->getStatusCode());
        $this->assertSame(200, $granted->getStatusCode());
    }
}
