# Session door — `RequireSession`

`JardisCore\App\Middleware\RequireSession` is a PSR-15 middleware that binds every request to a
verified session or answers `401` before the route handler runs. It depends only on the ports in
`jardissupport/contracts` (`^2.8`): `AuthenticatorInterface`, `CredentialInterface`,
`TenantAwareAuthResultInterface` and the attribute names `RequestAttribute::SUBJECT` /
`RequestAttribute::TENANT`. `jardiscore/app` does **not** require `jardissupport/auth`; the application does.

## Wiring with `jardissupport/auth`

```php
use JardisCore\App\Middleware\RequireSession;
use JardisCore\App\Routes;
use JardisSupport\Auth\Data\Credential;
use JardisSupport\Auth\DbTokenStore;
use JardisSupport\Auth\TokenAuthenticator;
use Nyholm\Psr7\Factory\Psr17Factory;

$psr17 = new Psr17Factory();
$routes = new Routes($psr17);

$authenticator = new TokenAuthenticator($tokenStore);     // $tokenStore: TokenStoreInterface, e.g. DbTokenStore

$routes->middleware(new RequireSession(
    $authenticator,
    Credential::token(...),                               // Closure(string): CredentialInterface
    $psr17,                                               // ResponseFactoryInterface
    $psr17,                                               // StreamFactoryInterface
    anonymousPaths: ['/auth/login', '/auth/refresh', '/docs/*'],
));
$routes->health('/health');                               // anonymous without being listed
```

A handler (or the bridge into the domain) reads the session from the request:

```php
$subject = $request->getAttribute(RequestAttribute::SUBJECT);   // string
$tenant  = $request->getAttribute(RequestAttribute::TENANT);    // string, or null when the session has no tenant
```

## Rules

| Topic | Behaviour |
|---|---|
| Token | `Authorization: Bearer <token>`: exactly one header line, scheme case-insensitive, token without whitespace. Constructor arguments `headerName` / `scheme` change it; `scheme: ''` takes the whole header value as token. |
| Rejection | `401`, body `{"status":401,"data":{},"errors":{"message":"Authentication required."},"meta":{}}` (built by `BuildErrorResponse`, the same envelope as every other answer), header `WWW-Authenticate: Bearer` (omitted when `scheme` is `''`). The handler is never reached. |
| Which tokens | Missing, malformed, unknown, expired and revoked tokens, and a "successful" result without subject, are answered identically; the failure reason stays inside the app. |
| Authenticator throws | A technical error (e.g. token store unreachable) is not a 401: it ends in the generic 500. |
| Session attributes | `jardisSubject` always, `jardisTenant` only if the result is `TenantAwareAuthResultInterface` with a non-empty tenant; otherwise the attribute is absent (not `null`, not `''`). |
| Forgery | Existing attributes of these names (an upstream middleware, a path placeholder such as `/x/{jardisSubject}`) are overwritten on a valid session and removed on an anonymous request. Headers never become attributes. |
| Anonymous paths | `anonymousPaths` takes exact paths (`/auth/login`) and prefixes ending in `*` (`/docs/*`), compared with the request path as the router sees it (percent-encoded, no normalisation), for all HTTP methods. |
| `health()` | Routes registered by `Routes::health()` are anonymous automatically (`Route::$anonymous`, request attribute `jardisAnonymousRoute`, a strict bool the dispatcher always writes). |
| 404 / 405 | Middleware only runs on a matched route: an unknown path stays `404`, a wrong method `405` - never `401`. |
| Scope | Register globally with `Routes::middleware()` (guards everything except anonymous paths) or per route: `$routes->get('/orders', $handler, $requireSession)`. |
