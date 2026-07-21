<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Acceptance;

use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * P7 §5: the ResponseStatus cases the generated Ecommerce fixture's own
 * `orders` routes never produce (204/401/403/409) — e2e via a synthetic
 * test-only route (honestly declared, {@see \ExampleApp\RegisterSyntheticRoutes}),
 * closing the P5→P7 AK-Matrix row "alle uebrigen Statuscodes e2e" (F4).
 *
 * Also carries the F2 PSR-Interop evidence: `middlewares/client-ip`
 * (a real, small, framework-free Packagist PSR-15 middleware) runs in the
 * example App's global pipeline without any Jardis adapter.
 */
final class BoundaryStatusAcceptanceTest extends EcommerceAcceptanceTestCase
{
    public function testNoContentTwoOhFourCarriesNoBody(): void
    {
        $app = $this->buildAppWithSyntheticRoutes();

        $response = $app->handle($this->request('GET', '/test/no-content'));

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
    }

    public function testUnauthorizedFourOhOne(): void
    {
        $app = $this->buildAppWithSyntheticRoutes();

        $response = $app->handle($this->request('GET', '/test/unauthorized'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(401, $this->decode($response)['status']);
    }

    public function testForbiddenFourOhThree(): void
    {
        $app = $this->buildAppWithSyntheticRoutes();

        $response = $app->handle($this->request('GET', '/test/forbidden'));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(403, $this->decode($response)['status']);
    }

    public function testConflictFourOhNine(): void
    {
        $app = $this->buildAppWithSyntheticRoutes();

        $response = $app->handle($this->request('GET', '/test/conflict'));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(409, $this->decode($response)['status']);
    }

    /**
     * F2 evidence (PLAN P7 Block B): `Middlewares\ClientIp` — a real
     * Packagist PSR-15 middleware, no framework dependency, wired via
     * `Routes::middleware()` alone (F3 global middleware) — runs and sets
     * the `client-ip` request attribute; the thin handler surfaces it as
     * a response header so the middleware's effect is independently
     * observable end-to-end.
     */
    public function testForeignPsr15MiddlewareRunsWithoutAnyJardisAdapter(): void
    {
        $app = $this->buildAppWithSyntheticRoutes();

        $factory = new Psr17Factory();
        $request = $factory->createServerRequest('GET', '/debug/client-ip', ['REMOTE_ADDR' => '203.0.113.42']);

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('203.0.113.42', $response->getHeaderLine('X-Client-Ip'));
    }
}
