---
name: core-app
description: "jardiscore/app v3 - HTTP-Delivery layer for Jardis domains: FastRoute router behind RouterInterface, PSR-15 middleware pipeline, DomainResponse-to-PSR-7 envelope mapper, kernel bootstrap bridge. Use when wiring routes, middleware, RequireRight, X-Jardis-Version."
user-invocable: false
zone: post-active
persona: D
prerequisites: [foundation-architecture, foundation-patterns, core-kernel]
next: []
---

This skill is an index; the detail lives in `reference/`, one leaf per topic. Read exactly the leaf whose question matches your task, not all of them.
The route registration API (ROUTES), the JSON body parser, the `X-Jardis-Version` contract, the bootstrap recipe, the rules and the dependencies stay in this index.

## Triggers

jardiscore/app v3 - the HTTP-Delivery layer for Jardis domains, a FastRoute router hidden behind an own RouterInterface, a PSR-15 middleware pipeline, the canonical DomainResponse-to-PSR-7 envelope mapper, and a DomainKernel bootstrap bridge. TRIGGER: ReadRequestAttribute, ReadRequestAttributeList, ReadRequestAttributeIntList, ReadSeesAll, ResolveGroups, ApplyGroups, ResolveFallbackGroups, ApplyFallbackGroups, jardisFallbackGroups, ReadRequestAttributeInt, jardisGroups, jardisPersonalGroup, GroupLookupInterface, ReadRequestAttributeStrict, jardisRights, RequestAttribute::RIGHTS, RequireRight, access.rightMissing, HasRight, RevokeSessionOnSuccess, SignedInRun, SystemRunRefused, SessionDoor, PersonalGroupPerCreator, FallbackGroupPerTenant, RepeatWhileOpen, RepeatAborted, system run, invalidateByReference, logout, revoke session, AnswerUnsupportedRequest, App, Routes, Router, RouterInterface, ParseJsonBody, X-Jardis-Version, knownVersions, jardisVersion, EmitResponse, HandleThrowable, MapDomainResponse, BuildErrorResponse, jardiscore/app, response envelope, {status,data,errors,meta}, health(), auto-HEAD, middleware: when and where, RequireSession, RequireRequestAttribute, request.attributeMissing, required request attribute, 403, per-route middleware.

# APP_COMPONENT_SKILL
> `jardiscore/app` | NS: `JardisCore\App` | PHP 8.3+

## Navigation

Navigation: one leaf per topic under `reference/`. Pick the row whose question fits the task.

| Question the leaf answers | Leaf |
| --- | --- |
| Which classes does the package have, with constructor and `__invoke` signatures (App, Routes, Router, AppConfig, request/response/session/directory closures, middleware, `SignedInRun`, VOs, exceptions)? | reference/classes.md |
| Is it middleware at all, what is fixed in the chain and what is pluggable, where does the pipeline live in the app template, and how do `RequireSession`, the 401, the session attributes and `requireSessionGuard` work? | reference/middleware-and-session.md |
| How do `ResolveGroups`, `ResolveShareTargets`, `ResolveFallbackGroups` and the sees-all tick work for per-record access? | reference/groups-and-share-targets.md |
| How do `RequireRequestAttribute`, `RequireRight` (403) and `RevokeSessionOnSuccess` (logout) work, and how does a system run (`SignedInRun`) sign in? | reference/route-guards-and-system-run.md |
| Which status maps to which HTTP code, what does the `{status,data,errors,meta}` envelope look like, how are errors, 500 and `debug` answered, and what about auto-HEAD and OPTIONS? | reference/envelope-and-errors.md |

## ROUTES — registration API
```php
use JardisCore\App\Routes;
use Nyholm\Psr7\Factory\Psr17Factory;

$routes = new Routes(new Psr17Factory());   // ResponseFactoryInterface — used only by health()

$routes->get('/orders/{id}', $handler, ...$middleware);      // MiddlewareInterface ...$middleware, variadic
$routes->post('/orders', $handler);
$routes->put('/orders/{id}', $handler);
$routes->patch('/orders/{id}', $handler);
$routes->delete('/orders/{id}', $handler);

$routes->middleware($globalMiddleware);      // applied to every route (outer), registration order
$routes->health('/health');                  // GET, always 200 {"status":200} — M10, never touches a domain
$routes->anonymous()->post('/auth/login', $h); // same verbs; the route needs no session (Route::$anonymous)
```
Each verb accepts `callable|RequestHandlerInterface`; a callable is converted to a `Closure` immediately on
registration (E4) — every collected `Route` already satisfies `Closure|RequestHandlerInterface`.

**Path placeholders arrive decoded.** Matching runs on the percent-encoded PSR-7 path; `DispatchRequest` then
`rawurldecode`s every placeholder value exactly once before it lands in `RouteMatch::$parameters` and, via
`ApplyRouteAttributes`, in `$request->getAttribute('email')` (`%40`→`@`, `%20`→space, `%C3%BC`→`ü`, `%2F`→`/` inside
the value without changing the route match; `+` stays `+`). Handlers never decode again (a second decode would corrupt
a literal `%`).

## PARSEJSONBODY — the E7 contract
`ParseJsonBody::__invoke(ServerRequestInterface): array` throws `Exception\InvalidJsonBody` for exactly three
cases: **empty raw body**, **JSON syntax error** (`JSON_THROW_ON_ERROR`), or a **decoded value that is not an
array** (scalar/`null` JSON). Raw-Body-Invariante: rewinds the body stream before and after reading, so the
same request's raw body can be read again afterwards (webhook-HMAC use case) and calling the parser twice is
idempotent. The decoded array goes on to the request mapper of `jardissupport/response` ^3.0 || ^4.0 (`Handler\Request\Read*` with an `Accept\*` closure, `Handler\Answer\AnswerInvalidRequest` -> 400; skill `support-response`) - no longer part of this package (2.0.0).

## X-JARDIS-VERSION — the class-version contract
`App` reads the request header `X-Jardis-Version` ONCE (`Handler\Request\ResolveVersion`, called by `DispatchAndRespond`
on a Found route, after the path attributes and BEFORE the middleware pipeline) and sets the request attribute
`jardisVersion` (`DispatchAndRespond::VERSION_ATTRIBUTE`): header absent → `''` (baseline); one value, exactly (case-sensitive)
in `AppConfig::$knownVersions` → that value. Unknown value, empty value or repeated header → **400** in the validation form,
no middleware and no handler runs: `data = {"@type":"validation","fields":[{"field":"X-Jardis-Version","reason":"invalid",
"message":"The requested version is not known.","messageKey":"request.versionUnknown"}]}`, `errors = {}`, `meta = {}`.
404/405 stay as before (the check only runs on a Found route). Default `knownVersions = []` → only the baseline is reachable;
an app that never receives the header behaves as before. Wiring: `new AppConfig(knownVersions: ['v2', 'v3'])`; a handler
reads `$request->getAttribute('jardisVersion')`. The set of known versions comes from outside (the Builder generates it
from the domain manifest); this package scans no directories and knows no classes.

## BOOTSTRAP-REZEPT
`BuildDomainKernelFromEnv` (packer) → `DomainKernel` (DomainKernel) → Builder-generated domain(s) (`new
{Domain}($kernel)`, K8, not part of this package) → `Routes` + handlers → `App` → `run()`. Full runnable
`public/index.php` incl. a fatal-error shutdown-function safety net, `AppConfig::debug` wired from
`$kernel->env('app_debug')`, and API-versioning-by-parameter: `docs/getting-started.md`.

## EXAMPLES
- `examples/basic/` — the full recipe wired against a **real** Builder-generated domain (`Ecommerce`/`Sales`)
  over MySQL: `GET/POST/PATCH /orders`, the 422 rule-violation payload, 404/405/500 boundary cases, and a real
  PSR-15 ecosystem middleware (`middlewares/client-ip`).
- `examples/symfony-demo/` (N3) — a **separate, isolated** Composer project answering the same
  `{status,data,errors,meta}` envelope for the same vendored `Ecommerce` fixture via **Symfony**
  (`symfony/http-foundation` + `symfony/routing`) instead of `jardiscore/app` — illustrates that no domain
  depends on this package.

## RULES
- A route handler must return `DomainResponseInterface` or a PSR-7 `ResponseInterface` — anything else throws
  `UnresolvableHandlerResult`, which propagates to the generic 500 boundary (E6/E8).
- `AppConfig` never reads ENV itself — the bootstrap layer always injects `debug` and `knownVersions` explicitly (E11).
- PSR-17 factories are received as interfaces only; `Nyholm\Psr7\Factory\Psr17Factory` is `App`'s own
  swappable-by-injection default, never hardwired inside handler/business code (E12-symmetric).
- Request-body size limits, Trusted-Proxy/`X-Forwarded-*`, and `display_errors=Off` are deliberately **not**
  solved by this package (N1) — named responsibilities of the surrounding webserver/FPM/proxy infrastructure.
- Typed coercion of path/query/body parameters and the 400 for a bad request are NOT in this package (since 2.0.0): the request mapper
  lives in `jardissupport/response` ^3.0 (skill `support-response`); business validation is `jardissupport/validation` / a Rule.

## DEPENDENCIES
```
jardiscore/kernel            ^2.0
jardissupport/contracts      ^2.22
jardissupport/response       ^3.0 || ^4.0
jardissupport/factory        ^1.0
nikic/fast-route             ^1.3
nyholm/psr7                  ^1.8
nyholm/psr7-server            ^1.1
psr/http-message              ^2
psr/http-factory              ^1
psr/http-server-handler       ^1
psr/http-server-middleware    ^1
```
PHP >= 8.3.
