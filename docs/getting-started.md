# Getting started — `jardiscore/app`

A minimal, runnable `public/index.php` recipe for wiring the Jardis App-Layer around a
DomainKernel (`DomainKernel`) and one or more Builder-generated domains.

**Measurement frame (PRD §5, K6):** the steps below take a PHP developer with a prepared
environment (PHP 8.3 + Composer already installed) under 15 minutes, from
`composer require` to the first successful `200` response — no lab benchmark, but a
defined baseline. The single recipe below covers, in order: install, ENV/DomainKernel
Bootstrap, domain registration, the first route + handler, starting the server, and the
first successful request.

## 1. Install

```bash
composer require jardiscore/app
```

## 2. ENV / DomainKernel Bootstrap

`BuildDomainKernelFromEnv` (from `jardiscore/kernel`, this package's own dependency) takes
the **project root** — the git-clone target — and packs a `DomainKernel` from the
cascading `.env` tree it finds under the fixed `<projectRoot>/config/env/` convention (see
the kernel's
[`docs/env-examples/`](https://github.com/jardisCore/kernel/tree/main/docs/env-examples)
for the full key reference: `DB_*`, `CACHE_*`, `LOG_*`, …). A missing `config/env/`
directory is created on first boot, not an error; every service it wires is optional, so a
`DomainKernel` without a configured logger, cache, etc. is perfectly valid. `app_debug`
(read via `$kernel->env('app_debug')`, step 3 below) gates whether the F9 boundary
500-response includes exception details — keep it off in production.

## 3. Domain Registration

**Ownership boundary (PRD F5, K8):** the Builder generates your domain composition —
typically an `App/bootstrap.php` next to this file that builds every domain facade from
the kernel (`new Sales($kernel)`, …). This package never replaces that file; it supplies
the HTTP entry point *around* it.

## 4. First Route + Handler, Start, First Request

The full recipe — `public/index.php` — wires all of the above plus the first route and
starts the server:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use JardisCore\App\App;
use JardisCore\App\Config\AppConfig;
use JardisCore\App\Routes;
use JardisCore\Kernel\Bootstrap\BuildDomainKernelFromEnv;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;

// --- Fatal-error safety net (E8: bootstrap can fail before any pipeline
// exists to catch it - a shutdown function is the last line of defense for
// truly fatal errors, e.g. a parse error deep in a required file). Keep
// `display_errors=Off` in production (php.ini or `-d display_errors=Off`)
// so a fatal error never leaks a stack trace to the client; this function
// only ensures the client still gets *a* response and the cause is logged.
register_shutdown_function(static function (): void {
    $error = error_get_last();
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    if ($error !== null && in_array($error['type'], $fatal, true)) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        error_log(sprintf('Fatal error: %s in %s:%d', $error['message'], $error['file'], $error['line']));
    }
});

// --- 1. Bootstrap the DomainKernel from the project root's cascading .env
// files under config/env/ (the ENV-Packer, jardiscore/kernel). A throw here
// (e.g. an unwritable config/env/) is the "Bootstrap-Fehlschlag" case (PRD
// F9): nothing has caught it yet, so PHP's own error handling takes over - a
// bare 500 (via display_errors=Off + the shutdown function above) and the
// real cause in stderr/error_log.
$kernel = (new BuildDomainKernelFromEnv())(__DIR__ . '/..');

// --- 2. Domain composition (Builder-generated, K8 - not part of this
// package). Uncomment once your workspace has generated it:
// require __DIR__ . '/../App/bootstrap.php';
// $sales = new \Ecommerce\Sales($kernel);

// --- 3. AppConfig: values are always injected from the kernel's ENV, never
// read by the VO itself (E11). `app_debug` gates whether the generic 500
// boundary response (F9) includes exception details.
$config = new AppConfig(debug: (bool) $kernel->env('app_debug'));

// --- 4. Routes: register the health endpoint (M10) plus your own routes.
// A route handler returns EITHER a DomainResponseInterface (from a BC's
// read-facade/process()/exposed rule-guarded command, F6) OR a ready-built
// PSR-7 ResponseInterface - both are auto-mapped (E6).
$routes = new Routes(new Psr17Factory());
$routes->health('/health');

$routes->get('/orders/{id}', static function (ServerRequestInterface $request) {
    $id = (string) $request->getAttribute('id');

    // return $sales->order()->getOrderById($id); // BC read-facade (F6)

    $factory = new Psr17Factory();
    $response = $factory->createResponse(200)->withHeader('Content-Type', 'application/json');
    $response->getBody()->write(json_encode(['id' => $id], JSON_THROW_ON_ERROR));

    return $response;
});

// --- 5. App + run(): builds the request from the SAPI globals, runs it
// through the pipeline, emits the response.
$app = new App($routes, $kernel, $config);
$app->run();
```

## Start it

```bash
php -d display_errors=Off -S 127.0.0.1:8080 -t public public/index.php
curl -s http://127.0.0.1:8080/health
# {"status":200}
curl -s http://127.0.0.1:8080/orders/42
# {"id":"42"}
```

`display_errors=Off` is deliberate (see the shutdown-function comment above): a
production-like run never leaks a fatal error's message or stack trace to the client.
`-t public` serves everything else in `public/` as static files (FastCGI/Apache
deployments configure this via the webserver's document root instead).

## N1 — Deployment levers (production)

`jardiscore/app` deliberately does not solve these — they stay a named
responsibility of the surrounding infrastructure (PRD N1):

- **Request-body size limits.** No limit is enforced in the App-Layer core —
  configure `client_max_body_size` (nginx) and `post_max_size`/`upload_max_filesize`
  (PHP-FPM `php.ini`) at the webserver/FPM layer.
- **Trusted-Proxy / `X-Forwarded-*`.** Not handled in the core — behind a reverse
  proxy or load balancer, add a PSR-15 Trusted-Proxy middleware (ecosystem package,
  F2 interop) if the app needs the real client IP/scheme/host.
- **`display_errors=Off` in production.** As shown above: a forgotten debug flag
  must never leak a stack trace to a client — keep `display_errors=Off`
  (`php.ini` or `-d display_errors=Off`) and drive exception detail exclusively
  through `AppConfig`'s `debug` flag (`APP_DEBUG` ENV, F9), never through PHP's
  own error display.

## See also

`examples/basic/` (P7) wires this exact recipe against a REAL Builder-generated
domain (`Ecommerce`/`Sales`) over MySQL — `GET/POST/PATCH /orders`, the 422
rule-violation payload, 404/405/500 boundary cases, and a real PSR-15
ecosystem-middleware (`middlewares/client-ip`) all in one runnable example. Start
there to see every piece of this recipe wired against generated code instead of
the inline stub handler above.

**Note on `APP_DEBUG` in that example:** `examples/basic/public/index.php`
reads `$debug = (bool) ($_ENV['APP_DEBUG'] ?? false)` directly, unlike the
`(bool) $kernel->env('app_debug')` read in step 3 above. This is not an E11
violation (E11 governs `src/`, not example bootstrap code) and not an
inconsistency to fix: that example wires its `DomainKernel` directly
(`ExampleAppFactory::kernel()`, no `BuildDomainKernelFromEnv`), and `$debug`
is a constructor argument `ExampleAppFactory` needs *before* it builds the
kernel — there is no DomainKernel yet at the point `$debug` is resolved, so
`$kernel->env(...)` isn't available to read it from. Once a DomainKernel exists,
`$kernel->env(...)` is the canonical read for any ENV key, `app_debug`
included; before one exists (as in that example's bootstrap), `$_ENV`/
`getenv()` is the only source there is.

## API Versioning (M9, v1 recipe)

v1 does not prescribe a versioning mechanism. `version` is just a domain parameter a
handler can set from whatever source is appropriate - a URL prefix or a header:

```php
$routes->get('/v2/orders/{id}', static function (ServerRequestInterface $request) use ($sales) {
    $id = (string) $request->getAttribute('id');

    return $sales->context(GetOrderById::class, ['id' => $id], version: 'v2');
});
```

## Validation Note

Typed coercion of path/query/body parameters (string → int, …) is the route handler's
job in v1 (F8). For anything beyond trivial casts, reach for `jardissupport/validation`
instead of ad-hoc checks - it is the dedicated Jardis package for object-graph
validation and composes cleanly with a thin handler.
