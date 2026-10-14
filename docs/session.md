# Session door — `RequireSession`

`JardisCore\App\Middleware\RequireSession` is a PSR-15 middleware that binds every request to a
verified session or answers `401` before the route handler runs. It depends only on the ports in
`jardissupport/contracts` (`^2.9`): `AuthenticatorInterface`, `CredentialInterface`,
`TenantAwareAuthResultInterface` and the attribute names `RequestAttribute::SUBJECT` /
`TENANT` / `SUBJECT_ID` / `SESSION`. `jardiscore/app` does **not** require `jardissupport/auth`; the application does.

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
| Subject id / session reference | `jardisSubjectId` and `jardisSession` only if the result is a `SessionAwareAuthResultInterface` (`getSubjectId()` / `getSessionReference()`, contracts ^2.9) and the value is non-empty; otherwise absent (not `null`, not `''`). A plain tenant-aware result leaves both absent. |
| Forgery | Existing attributes of these names (an upstream middleware, a path placeholder such as `/x/{jardisSubject}`, `/x/{jardisSession}`) are overwritten on a valid session and removed on an anonymous request. Headers never become attributes. |
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
