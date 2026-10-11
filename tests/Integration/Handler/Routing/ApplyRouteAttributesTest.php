<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Routing;

use JardisCore\App\Handler\Routing\ApplyRouteAttributes;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ApplyRouteAttributes (F8a): the documented call site that
 * applies a matched route's path parameters (already decoded by
 * DispatchRequest) as request attributes, verbatim - no second decoding.
 */
final class ApplyRouteAttributesTest extends TestCase
{
    public function testAppliesEachParameterAsARequestAttribute(): void
    {
        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('GET', '/orders/42/items/items-1');
        $apply = new ApplyRouteAttributes();

        $result = $apply($request, ['id' => '42', 'sub' => 'items-1']);

        $this->assertSame('42', $result->getAttribute('id'));
        $this->assertSame('items-1', $result->getAttribute('sub'));
    }

    public function testDoesNotDecodeAgain(): void
    {
        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('GET', '/leads/x');

        $result = (new ApplyRouteAttributes())($request, ['email' => '100%40x']);

        $this->assertSame('100%40x', $result->getAttribute('email'));
    }

    public function testEmptyParametersReturnTheRequestUnchanged(): void
    {
        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('GET', '/health');
        $apply = new ApplyRouteAttributes();

        $result = $apply($request, []);

        $this->assertSame($request, $result);
    }
}
