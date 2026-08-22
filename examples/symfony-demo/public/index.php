<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use JardisCore\Kernel\DomainKernel;
use SymfonyDemo\BuildBoundaryEnvelope;
use SymfonyDemo\OrderController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * N3 illustration front controller (PRD N3, PLAN P8 Block A): "same domain,
 * Symfony instead of the Jardis Router" -- proving the DomainResponse ->
 * HTTP envelope contract (`vendor/jardissupport/contracts/docs/response-envelope.md`)
 * is framework-neutral, not an `jardiscore/app` lock-in. This file never
 * imports `jardiscore/app` (see `examples/symfony-demo/composer.json` --
 * only `symfony/http-foundation` + `symfony/routing` + the kernel/contract/
 * support packages the vendored Ecommerce fixture itself needs).
 *
 * The route table intentionally mirrors ONLY `GET /orders/{id}` from
 * `examples/basic/app/RegisterOrderRoutes.php` -- enough to run the
 * curl-comparison in this directory's README, not a second App-Layer.
 */
$routes = new RouteCollection();
$routes->add('order_get', new Route(
    '/orders/{id}',
    ['_controller' => 'getOrderById'],
    ['id' => '\d+'],
    [],
    '',
    [],
    ['GET'],
));

$request = Request::createFromGlobals();
$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);

try {
    $attributes = $matcher->match($request->getPathInfo());

    // Same fixture, same domainRoot the vendored Ecommerce fixture uses
    // under examples/basic (K5/B5: this demo depends on the fixture via a
    // relative autoload path, `composer.json`'s `Ecommerce\` psr-4 entry --
    // never the other way around).
    //
    // This directory is its own isolated Composer project with a committed
    // vendor/ (see README) deliberately left on jardiscore/kernel v1 -- its
    // DomainKernel constructor still takes `domainRoot`, not the `projectRoot`
    // the rest of this package moved to (env-konfiguration, R2). Migrating
    // this demo is its own follow-up, not part of that move.
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['MYSQL_HOST'] ?? 'mysql',
            $_ENV['MYSQL_PORT'] ?? 3306,
            $_ENV['MYSQL_DATABASE'] ?? 'test_db',
        ),
        $_ENV['MYSQL_USER'] ?? 'test_user',
        $_ENV['MYSQL_PASSWORD'] ?? 'test_password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $kernel = new DomainKernel(
        domainRoot: __DIR__ . '/../../basic/domain/Ecommerce',
        connection: $pdo,
    );

    $controller = new OrderController($kernel);
    $response = $controller->getOrderById((string) $attributes['id']);
} catch (ResourceNotFoundException) {
    // F9: no matching route -> same envelope as a domain-produced error.
    $response = (new BuildBoundaryEnvelope())(404);
} catch (MethodNotAllowedException $exception) {
    // F9: wrong HTTP method -> same envelope + RFC 7231 Allow header.
    $response = (new BuildBoundaryEnvelope())(405, $exception->getAllowedMethods());
} catch (\Throwable $exception) {
    // F9: generic 500 to the client (no message/trace/class name in the
    // body) -- but the real cause still needs a server-side trail, exactly
    // as `jardiscore/app`'s own `Handler/Error/HandleThrowable` logs before
    // answering generically. This demo has no Koffer-Logger wired (no
    // route here needs one), so `error_log` is the same documented
    // fallback the App-Layer itself uses when its own logger throws.
    error_log(sprintf('Symfony demo: %s: %s', $exception::class, $exception->getMessage()));
    $response = (new BuildBoundaryEnvelope())(500);
}

$response->send();
