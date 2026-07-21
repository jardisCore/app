<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Routing;

use JardisCore\App\Handler\Routing\WrapCallableHandler;
use JardisCore\App\Tests\Support\FixedResponseHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Tests for WrapCallableHandler (E4): precedence between
 * RequestHandlerInterface and bare Closure.
 */
final class WrapCallableHandlerTest extends TestCase
{
    public function testRequestHandlerInterfaceIsCheckedFirstAndReturnedUnchanged(): void
    {
        $factory = new Psr17Factory();
        $handler = new FixedResponseHandler($factory->createResponse(200));
        $wrap = new WrapCallableHandler();

        $wrapped = $wrap($handler);

        $this->assertSame($handler, $wrapped);
    }

    public function testClosureIsWrappedIntoARequestHandlerInterface(): void
    {
        $factory = new Psr17Factory();
        $response = $factory->createResponse(201);
        $wrap = new WrapCallableHandler();

        $wrapped = $wrap(static fn () => $response);

        $this->assertInstanceOf(RequestHandlerInterface::class, $wrapped);
        $this->assertSame($response, $wrapped->handle($factory->createServerRequest('GET', '/x')));
    }
}
