<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Response;

use JardisCore\App\Exception\UnresolvableHandlerResult;
use JardisCore\App\Handler\Response\ResolveResponse;
use JardisCore\App\Tests\Support\DomainResponse;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ResolveResponse (E6): the auto-mapping Closure that turns a
 * handler's return value into a PSR-7 ResponseInterface. All three
 * branches: DomainResponseInterface -> MapDomainResponse, ResponseInterface
 * -> pass-through, anything else -> UnresolvableHandlerResult (escapes to
 * HandleThrowable's generic 500 path, E8).
 */
final class ResolveResponseTest extends TestCase
{
    private function resolve(): ResolveResponse
    {
        $factory = new Psr17Factory();

        return new ResolveResponse($factory, $factory);
    }

    public function testDomainResponseInterfaceIsMappedThroughTheCanonicalEnvelopeMapper(): void
    {
        $resolve = $this->resolve();
        $domainResponse = new DomainResponse(status: ResponseStatus::Created->value, data: ['id' => '42']);

        $response = $resolve($domainResponse);

        $this->assertSame(201, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(['id' => '42'], $body['data']);
    }

    public function testPsr7ResponseInterfaceIsPassedThroughUnchanged(): void
    {
        $resolve = $this->resolve();
        $factory = new Psr17Factory();
        $expected = $factory->createResponse(200);

        $response = $resolve($expected);

        $this->assertSame($expected, $response);
    }

    public function testAnyOtherReturnValueThrowsUnresolvableHandlerResult(): void
    {
        $resolve = $this->resolve();

        $this->expectException(UnresolvableHandlerResult::class);
        $this->expectExceptionMessage('array given.');

        $resolve(['not' => 'a response']);
    }

    public function testNullReturnValueThrowsUnresolvableHandlerResult(): void
    {
        $resolve = $this->resolve();

        $this->expectException(UnresolvableHandlerResult::class);
        $this->expectExceptionMessage('null given.');

        $resolve(null);
    }
}
