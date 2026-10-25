# Session door — `RequireSession`

`JardisCore\App\Middleware\RequireSession` is a PSR-15 middleware that binds every request to a
verified session or answers `401` before the route handler runs. It depends only on the ports in
`jardissupport/contracts` (`^2.11`): `AuthenticatorInterface`, `CredentialInterface`,
`TenantAwareAuthResultInterface` and the attribute names `RequestAttribute::SUBJECT` /
`TENANT` / `ROLE` / `RIGHTS` / `SUBJECT_ID` / `SESSION`. `jardiscore/app` does **not** require `jardissupport/auth`; the application does.

## Wiring with `jardissupport/auth`

```php
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\SignedTokenAuthenticator;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr17 = new Psr17Factory();
$routes = new Routes($psr17);

// Access tokens issued by SessionManager are signed: verified with the 32-byte key, no store, no database.
$authenticator = new SignedTokenAuthenticator($accessTokenKey);

$routes->middleware(new RequireSession(
    $authenticator,
    Credential::token(...),                               // Closure(string): CredentialInterface
    $psr17,                                               // ResponseFactoryInterface
    $psr17,                                               // StreamFactoryInterface
));
$routes->anonymous()->post('/auth/login', $login);        // routes that need no session
$routes->health('/health');                               // anonymous automatically
```

A handler (or the bridge into the domain) reads the session from the request:

```php
$subject = $request->getAttribute(RequestAttribute::SUBJECT);   // string
$tenant  = $request->getAttribute(RequestAttribute::TENANT);    // string, or null when the session has no tenant
$id      = $request->getAttribute(RequestAttribute::SUBJECT_ID); // bare id ('42' for 'user:42'), null unless the result is session-aware
$ref     = $request->getAttribute(RequestAttribute::SESSION);    // session reference, null unless the result is session-aware
```

Revoke the caller's own session (log out) - no token parsing, no `user:<id>` splitting in the consumer:

```php
$sessionManager->invalidateByReference($request->getAttribute(RequestAttribute::SESSION));
```

## Rules

| Topic | Behaviour |
|---|---|
| Token | `Authorization: Bearer <token>`: exactly one header line, scheme case-insensitive, token without whitespace. Constructor arguments `headerName` / `scheme` change it; `scheme: ''` takes the whole header value as token. |
| Rejection | `401`, body `{"status":401,"data":{},"errors":{"message":"Authentication required."},"meta":{}}` (built by `BuildErrorResponse`, the same envelope as every other answer), header `WWW-Authenticate: Bearer` (omitted when `scheme` is `''`). The handler is never reached. |
| Which tokens | Missing, malformed, unknown, expired and revoked tokens, and a "successful" result without subject, are answered identically; the failure reason stays inside the app. |
| Authenticator throws | A technical error (e.g. token store unreachable) is not a 401: it ends in the generic 500. |
| Session attributes | `jardisSubject` always, `jardisTenant` only if the result is `TenantAwareAuthResultInterface` with a non-empty tenant; otherwise the attribute is absent (not `null`, not `''`). |
| Role | `jardisRole` only if the result is a `RoleAwareAuthResultInterface` (`getRole()`, contracts ^2.11, auth ^1.6) and the role is non-empty; otherwise absent (not `null`, not `''`). A plain attribute, not a guard - authorization is the application's job. |
| Rights | `jardisRights` (`RequestAttribute::RIGHTS`) only if the result is a `RightsAwareAuthResultInterface` (`getRights()`, contracts ^2.16, auth ^1.8) and the list is non-empty; otherwise absent (not `[]`, not `null`). The value is a `list<string>`; a route reads it with `Handler\Request\ReadRequestAttributeList` (absent -> `[]`, not a list of strings -> `null` = unusable). A plain attribute, not a guard - authorization is the application's job. |
| Subject id / session reference | `jardisSubjectId` and `jardisSession` only if the result is a `SessionAwareAuthResultInterface` (`getSubjectId()` / `getSessionReference()`, contracts ^2.9) and the value is non-empty; otherwise absent (not `null`, not `''`). A plain tenant-aware result leaves both absent. |
| Forgery | Existing attributes of these names (an upstream middleware, a path placeholder such as `/x/{jardisSubject}`, `/x/{jardisSession}`, `/x/{jardisRole}`, `/x/{jardisRights}`) are overwritten on a valid session and removed on an anonymous request. Headers never become attributes. |
| Anonymous routes | Only routes registered by `Routes::anonymous()` or `Routes::health()` pass without a session; there is no path-pattern list. |
| `health()` | Routes registered by `Routes::health()` are anonymous automatically (`Route::$anonymous`, request attribute `jardisAnonymousRoute`, a strict bool the dispatcher always writes). |
| 404 / 405 | Middleware only runs on a matched route: an unknown path stays `404`, a wrong method `405` - never `401`. |
| Scope | Register globally with `Routes::middleware()` (guards everything except anonymous routes) or per route: `$routes->get('/orders', $handler, $requireSession)`. |

## Anonymous routes, start-up check

- **`Routes::anonymous()`** returns a registrar with the same verbs (`get/post/put/patch/delete`); routes registered
  through it carry `Route::$anonymous`, exactly like `health()`: the session door lets them pass.
- `RequireSession` implements `Contract\SessionGuardInterface`, a marker. The wiring (which authenticator, which key) is not part of
  this package: the application builds the middleware itself (the Jardis builder generates `src/App/middleware.php`).
- **`AppConfig::$requireSessionGuard`** (default `false`): when `true`, `new App(...)` throws a `LogicException` naming every
  non-anonymous route (`METHOD path`) whose chain `[...global, ...route]` holds no `SessionGuardInterface`. The check
  knows no order and no decorators: a middleware wrapping `RequireSession` does not count unless it implements the marker.
  Fix: add a `SessionGuardInterface` middleware (e.g. `RequireSession`) to the `middleware` list, or open the route with `Routes::anonymous()`.

```php
$routes->middleware(new RequireSession($authenticator, Credential::token(...), $psr17, $psr17));
$routes->anonymous()->post('/auth/login', $login);
$app = new App($routes, $kernel, new AppConfig(requireSessionGuard: true));
```

## Required attributes - `RequireRequestAttribute`

`JardisCore\App\Middleware\RequireRequestAttribute` is a second PSR-15 middleware for a door that depends on a request
attribute, e.g. a route that needs a tenant. It checks presence only; it authorizes nothing (no role or ownership check) and
changes neither `RequireSession` nor `ApplySession`.

```php
use JardisCore\App\Middleware\RequireRequestAttribute;
use JardisSupport\Contract\Auth\RequestAttribute;

$routes->middleware($requireSession);   // global: sets jardisSubject, jardisTenant (if any), ...
$routes->get('/projects', $handler, new RequireRequestAttribute($psr17, $psr17, RequestAttribute::TENANT));
```

Signature: `__construct(ResponseFactoryInterface, StreamFactoryInterface, string ...$names)`. An empty name list or an empty name throws
`InvalidArgumentException`.

| Aspect | Behaviour |
|---|---|
| Check | For each name, in the given order, the attribute must be a non-empty string. The first one that is not (absent, not a string, `''`) ends the request. |
| Answer | `403`, handler never called: `{status:403, data:{}, errors:{message, messageKey:"request.attributeMissing", context:{attribute:"<name>"}}, meta:{}}` (the same form as the 401: no `@type`). The key is the constant `RequireRequestAttribute::MESSAGE_KEY`. |
| Otherwise | The request passes on unchanged. |
| Order | Per route, behind the middleware that sets the attribute. Without a session the session door answers `401` first, never `403`. |
| Forgery | A path placeholder named like the attribute (`/x/{jardisTenant}`) does not satisfy the check: `ApplySession` removes it first. |
| 404 / 405 | Like every middleware it only runs on a matched route. |
| Guard | It does NOT implement `SessionGuardInterface`, so it never satisfies `AppConfig::$requireSessionGuard`. |

## The right of a route - `RequireRight`

`JardisCore\App\Middleware\RequireRight` (since 3.0.0) is the third per-route PSR-15 middleware: it asks whether the rights of the
session (`RequestAttribute::RIGHTS`, set by `ApplySession`) hold the one right the route needs. It is an orchestrator: it reads the list
(`Handler\Request\ReadRequestAttributeList`), asks the injected check and routes - pass on or answer `403`. It decides nothing itself.

```php
use JardisCore\App\Middleware\RequireRight;
use JardisSupport\Auth\Handler\Authorization\HasRight;

$hasRight = (new HasRight())->__invoke(...);   // jardissupport/auth; this package does not require it
$routes->middleware($requireSession);          // global: 401 for a guest, sets jardisRights
$routes->get('/orders', $handler, new RequireRight($psr17, $psr17, 'sales.order:read', $hasRight));
```

Signature: `__construct(ResponseFactoryInterface, StreamFactoryInterface, string $right, Closure $hasRight)` with
`$hasRight: Closure(list<string> $rights, string $right): bool`. An empty `$right` throws `InvalidArgumentException`.

| Aspect | Behaviour |
|---|---|
| Check | `$hasRight($rights, $right)`; with `HasRight`: `resource:action`, `resource:*` and the superuser `*` match. |
| Answer | `403`, handler never called: `{"status":403,"data":{},"errors":{"message":"Right \"<right>\" is missing.","messageKey":"access.rightMissing","context":{"right":"<right>"}},"meta":{}}` (the same form as the other door answers: no `@type`). The key is the constant `RequireRight::MESSAGE_KEY`. |
| Fail-closed | RIGHTS absent (nothing granted), RIGHTS not a list of strings (unusable) and a check that throws `InvalidArgumentException` (a right it cannot parse) all answer the same `403` - never `200`, never `500`. |
| Guest | A request without a session never reaches it: the session door answers `401` first. Wired without `RequireSession`, the missing RIGHTS still denies. |
| Forgery | A path placeholder `{jardisRights}` cannot grant a right: `ApplySession` removes the attribute first. |
| Guard | It does NOT implement `SessionGuardInterface`, so it never satisfies `AppConfig::$requireSessionGuard`. |
