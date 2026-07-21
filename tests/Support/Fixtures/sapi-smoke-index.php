<?php

declare(strict_types=1);

// Fixture for the SAPI smoke test (P6 mandatory AK, E15): a minimal
// `public/index.php` stand-in run behind a real `php -S` built-in server.
// The Koffer is constructed directly (`new DomainKernel(...)`) - the
// ENV-Packer is not needed for this smoke test, only a real App wired
// around a real Router/pipeline/mapper answering over a real HTTP
// connection.
require dirname(__DIR__, 3) . '/vendor/autoload.php';

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Routes;
use JardisCore\Kernel\DomainKernel;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;

$kernel = new DomainKernel(domainRoot: __DIR__);
$config = new AppConfig();

$routes = new Routes(new Psr17Factory());
$routes->health('/health');

// Raw-Body-Invariante (PRD §5, P2 AK, echoed over the real SAPI here): the
// exact bytes received are written back unchanged.
$routes->post('/echo', static function (ServerRequestInterface $request) {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200);
    $response->getBody()->write((string) $request->getBody());

    return $response;
});

// A plain GET route to prove HEAD-auto-registration (E13) end to end
// through a real SAPI: HEAD must suppress the 'hello' body but still carry
// the correct Content-Length.
$routes->get('/greet', static function () {
    $factory = new Psr17Factory();
    $response = $factory->createResponse(200);
    $response->getBody()->write('hello');

    return $response;
});

$app = new App($routes, $kernel, $config);
$app->run();
