<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Integration\Handler\Request;

use JardisCore\App\Handler\Request\ResolveVersion;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Integration tests for ResolveVersion - the one reader of the
 * `X-Jardis-Version` header. Requests are real nyholm server requests.
 */
final class ResolveVersionTest extends TestCase
{
    private function request(?string $header = null): ServerRequestInterface
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/orders');

        return $header === null ? $request : $request->withHeader('X-Jardis-Version', $header);
    }

    public function testAbsentHeaderIsTheBaseline(): void
    {
        $resolve = new ResolveVersion(['v2']);

        $this->assertSame('', $resolve($this->request()));
    }

    public function testKnownVersionIsReturnedAsSent(): void
    {
        $resolve = new ResolveVersion(['v2', 'v3']);

        $this->assertSame('v2', $resolve($this->request('v2')));
        $this->assertSame('v3', $resolve($this->request('v3')));
    }

    public function testUnknownVersionIsInvalid(): void
    {
        $this->assertNull((new ResolveVersion(['v2']))($this->request('v9')));
    }

    public function testVersionMatchIsCaseSensitive(): void
    {
        $this->assertNull((new ResolveVersion(['v2']))($this->request('V2')));
    }

    public function testEmptyAndBlankValueAreInvalid(): void
    {
        $resolve = new ResolveVersion(['v2', '']);

        $this->assertNull($resolve($this->request('')));
        $this->assertNull($resolve($this->request('   ')));
    }

    public function testRepeatedHeaderIsInvalidEvenWhenEveryValueIsKnown(): void
    {
        $request = $this->request('v2')->withAddedHeader('X-Jardis-Version', 'v3');

        $this->assertNull((new ResolveVersion(['v2', 'v3']))($request));
    }

    public function testHeaderNameIsCaseInsensitive(): void
    {
        $request = (new Psr17Factory())->createServerRequest('GET', '/orders')
            ->withHeader('x-jardis-version', 'v2');

        $this->assertSame('v2', (new ResolveVersion(['v2']))($request));
    }

    public function testWithoutKnownVersionsOnlyTheBaselineIsReachable(): void
    {
        $resolve = new ResolveVersion();

        $this->assertSame('', $resolve($this->request()));
        $this->assertNull($resolve($this->request('v2')));
    }
}
