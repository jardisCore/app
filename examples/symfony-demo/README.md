# Symfony demo — N3 austauschbarkeits-illustration

This directory is a **separate, isolated Composer project** — it has its own
`composer.json` and its own `vendor/`. It illustrates PRD requirement N3(b):
the same vendored Ecommerce fixture (`examples/basic/domain/Ecommerce/`),
answering `GET /orders/{id}` through a **Symfony** front controller instead
of the Jardis Router — **without ever importing `jardiscore/app`** — and
producing the *exact same* `{status, data, errors, meta}` JSON envelope
(`vendor/jardissupport/contracts/docs/response-envelope.md`).

This is the *illustration*, not the *proof*. The structural proof that no
domain can depend on the App-Layer package lives in
`tests/Acceptance/WallFreedomHarnessTest.php` (P7) — a composer/namespace
dependency-direction check. This demo only shows what that structural
freedom lets a *consumer* do: swap the transport framework, keep the domain,
keep the client contract.

## Why this stack (ENTSCHEIDUNG)

`symfony/http-foundation` + `symfony/routing` only — no `HttpKernel`,
no `FrameworkBundle`, no full Symfony skeleton. A hand-written front
controller (`public/index.php`) builds one `RouteCollection`, matches with
`UrlMatcher`, and calls a thin controller class — enough to demonstrate
"a real Symfony component stack answers the same contract", without
building a second, competing App-Layer. No PSR-7/PSR-15 bridge is used:
the controller talks to the Sales BC's read facade directly and maps the
resulting `DomainResponseInterface` straight onto a Symfony `Response`
(`MapDomainResponseToJsonResponse`) — there is no PSR-7 request/response on
this side of the fence to bridge in the first place.

`jardiscore/app`'s own `vendor/` stays free of `symfony/*` — this
project's `composer.json`/`vendor/` are entirely separate from the
package root's (`../../composer.json`). `tests/Acceptance/VendorIsolationTest.php`
(K5) checks the *package's own* `composer.lock`/`vendor/`, not this
directory, and stays green regardless of what this demo installs.

## What runs here

- `composer.json` — its own dependency set: `jardiscore/kernel`,
  `jardissupport/contracts` (+ the same `jardissupport/{data,dbquery,repository,
  validation,workflow,classversion,factory}` packages the vendored fixture
  itself needs — see `examples/basic/composer.json`'s `require-dev` for the
  same list under the package's own dev-only fixture wiring), and
  `symfony/http-foundation` + `symfony/routing`. The `Ecommerce\` PSR-4 root
  points at `../basic/domain/Ecommerce/` — a relative autoload path into the
  **same** fixture `examples/basic/` vendors, never a second copy.
- `public/index.php` — the front controller: matches `GET /orders/{id}`,
  builds a `DomainKernel` against the same fixture `domainRoot`
  (`examples/basic/domain/Ecommerce`), calls
  `(new Ecommerce($kernel))->sales()->order()->getOrderById(...)` (the exact
  same BC outer door `examples/basic/app/RegisterOrderRoutes.php` calls for
  the Jardis-Router version of this route, F6), and maps the result.
  Boundary cases (404 no route, 405 wrong method + `Allow` header, 500
  uncaught exception) are mapped the same way, by hand, against the
  contract doc — this is the F9 "boundary errors use the same envelope"
  rule, re-implemented once more here for symmetry.
- `src/MapDomainResponseToJsonResponse.php` — the domain-response mapper
  (E2/E2b/E2c), Symfony's `JsonResponse` instead of a PSR-7 response.
- `src/BuildBoundaryEnvelope.php` — the 404/405/500 envelope builder for
  requests that never reach domain code.
- `src/OrderController.php` — the one-route controller.

## Run it

Prerequisites: PHP 8.3+, Composer, and the same MySQL fixture database the
package's own Acceptance suite uses (`support/docker-compose.yml`,
`make start` from the package root — the demo reads the same `MYSQL_*` ENV
convention, default host `mysql`/port `3306` when run inside the `phpcli`
container's network, override via env vars when run elsewhere). Seed one
order first (either run the package's own Acceptance suite once against a
fresh database, or `POST /orders` — see `docs/getting-started.md` — against
either front controller below; both write through the same fixture tables).

```bash
cd examples/symfony-demo
composer install
php -d display_errors=Off -S 127.0.0.1:8081 -t public public/index.php
```

In a second terminal, start the Jardis-Router version of the same route
(`examples/basic`, from the package root):

```bash
php -d display_errors=Off -S 127.0.0.1:8080 -t examples/basic/public examples/basic/public/index.php
```

## The comparison (demo smoke)

```bash
curl -s http://127.0.0.1:8080/orders/1   # Jardis Router
curl -s http://127.0.0.1:8081/orders/1   # Symfony
```

Both answer with the **same** envelope. Executed once during P8 (2026-07-17,
`phpcli` container, `support/docker-compose.yml` MySQL service, one seeded
order `ORD-DEMO-001` via `POST /orders`):

```
Jardis Router : {"status":200,"data":{"GetOrderByIdHandler":{"order":{"id":1,"orderNumber":"ORD-DEMO-001", ...}}},"errors":{},"meta":{"duration":0.44,"contexts":["GetOrderByIdHandler"],"timestamp":"2026-07-16T23:57:43+00:00","version":""}}
Symfony demo  : {"status":200,"data":{"GetOrderByIdHandler":{"order":{"id":1,"orderNumber":"ORD-DEMO-001", ...}}},"errors":{},"meta":{"duration":0.02,"contexts":["GetOrderByIdHandler"],"timestamp":"2026-07-16T23:57:44+00:00","version":""}}
```

Identical `status`/`data`/`errors`, and identical `meta` *keys* — only
`meta.duration` (actual handler runtime) and `meta.timestamp` (wall-clock
second) differ, both real, request-scoped, non-deterministic values the
generated `ContextResponse` itself stamps on every call. This is not a
mapping discrepancy; a byte-for-byte diff on those two volatile fields would
fail for two consecutive requests through the *same* front controller too.

The boundary cases match byte-for-byte (no volatile fields there):

```bash
curl -s http://127.0.0.1:8080/does-not-exist   # {"status":404,"data":{},"errors":{},"meta":{}}
curl -s http://127.0.0.1:8081/does-not-exist   # {"status":404,"data":{},"errors":{},"meta":{}} — identical
curl -s -i -X DELETE http://127.0.0.1:8081/orders/1
# HTTP 405, Allow: GET, {"status":405,"data":{},"errors":{},"meta":{}}
```

## Non-goals

This is not a second App-Layer. It has no middleware pipeline, no
Raw-Body-Invariante handling, no health endpoint, no other routes — none of
that is the point here. The point is exactly one thing: the client-facing
contract (`response-envelope.md`) is framework-neutral, and a real,
independent framework stack can satisfy it against the same domain without
depending on `jardiscore/app` at all.
