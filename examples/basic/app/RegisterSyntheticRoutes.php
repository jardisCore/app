<?php

declare(strict_types=1);

namespace ExampleApp;

use Closure;
use ExampleApp\Support\StaticDomainResponse;
use JardisCore\App\Routes;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Test-only routes (PLAN P7 Block B), honestly declared as synthetic —
 * NOT part of the "getting started" recipe. The generated Ecommerce
 * fixture's own legitimate outer doors never produce 204/401/403/409
 * (only 200/201/404/405/422/500 occur on the real `/orders` routes), so
 * these routes exist purely to close the P5→P7 AK-Matrix row "alle
 * uebrigen Statuscodes e2e via synthetischer Test-Route" — each handler
 * returns a {@see StaticDomainResponse} Contract-Fake instead of calling
 * any BC.
 *
 * `/debug/client-ip` demonstrates the F2 PSR-15 interop evidence
 * (`middlewares/client-ip`, wired as global middleware in
 * `AppFactory`/`public/index.php`): the thin handler reads the
 * `client-ip` request attribute the foreign middleware set and echoes it
 * back as a response header, so the middleware's effect is independently
 * observable.
 */
final class RegisterSyntheticRoutes
{
    public function __invoke(Routes $routes): void
    {
        $routes->get('/test/no-content', $this->staticStatus(ResponseStatus::NoContent));
        $routes->get('/test/unauthorized', $this->staticStatus(ResponseStatus::Unauthorized));
        $routes->get('/test/forbidden', $this->staticStatus(ResponseStatus::Forbidden));
        $routes->get('/test/conflict', $this->staticStatus(ResponseStatus::Conflict));
        $routes->get('/debug/client-ip', $this->echoClientIp());
        $routes->get('/test/boom', $this->throwingHandler());
    }

    /**
     * Handler-Exception 500 case (PLAN P7 Block B, F9): a route handler
     * throwing unconditionally, exercised through the real App pipeline —
     * the same established pattern `AppTest::testExceptionInHandler...`
     * (P4/P6) already uses for `HandleThrowable`.
     */
    private function throwingHandler(): Closure
    {
        return static function (): never {
            throw new RuntimeException('synthetic handler exception (P7 500 case)');
        };
    }

    private function staticStatus(ResponseStatus $status): Closure
    {
        return static fn(): DomainResponseInterface => new StaticDomainResponse(status: $status->value);
    }

    private function echoClientIp(): Closure
    {
        return static function (ServerRequestInterface $request): ResponseInterface {
            $clientIp = (string) ($request->getAttribute('client-ip') ?? '');

            $factory = new Psr17Factory();
            $response = $factory->createResponse(200)->withHeader('X-Client-Ip', $clientIp);
            $response->getBody()->write('{"status":200}');

            return $response;
        };
    }
}
