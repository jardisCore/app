## REQUIRED ATTRIBUTES — `RequireRequestAttribute` (PSR-15, per route)
For a door that depends on a request attribute (e.g. a tenant). Presence check only - no role/ownership check (that is domain logic).
```php
use JardisCore\App\Middleware\RequireRequestAttribute;
use JardisSupport\Contract\Auth\RequestAttribute;

$routes->middleware($requireSession);
$routes->get('/projects', $handler, new RequireRequestAttribute($psr17, $psr17, RequestAttribute::TENANT));
```
- **Check:** per name, in order: the attribute must be a non-empty string (absent / not a string / `''` fails); the first failing name ends the request.
- **403:** `{status:403, data:{}, errors:{message, messageKey:"request.attributeMissing" (const `MESSAGE_KEY`), context:{attribute:"<name>"}}, meta:{}}` - same form as the 401, no `@type`; handler not called. Otherwise the request passes unchanged.
- **Order:** behind the middleware that sets the attribute; with no token the session door answers 401 first. A path placeholder `{jardisTenant}` cannot satisfy it (`ApplySession` strips it).
- **Not a guard:** does not implement `SessionGuardInterface` -> never satisfies `requireSessionGuard`. 404/405 unchanged (runs only on a matched route).

## RIGHT OF A ROUTE — `RequireRight` (PSR-15, per route, since 3.0.0)
The rights of the session (`RequestAttribute::RIGHTS`, list of `resource:action`) must hold the right of the route. Orchestrator: `ReadRequestAttributeList` -> injected check -> pass on or 403.
```php
use JardisCore\App\Middleware\RequireRight;
use JardisSupport\Auth\Handler\Authorization\HasRight;

$hasRight = (new HasRight())->__invoke(...);
$routes->middleware($requireSession);
$routes->get('/orders', $handler, new RequireRight($psr17, $psr17, 'sales.order:read', $hasRight));
```
- **403:** `{"status":403,"data":{},"errors":{"message":"Right \"sales.order:read\" is missing.","messageKey":"access.rightMissing" (const `MESSAGE_KEY`),"context":{"right":"sales.order:read"}},"meta":{}}`; handler not called. Superuser `*` and `resource:*` pass (via `HasRight`).
- **Fail-closed:** RIGHTS absent, not a list of strings, or a check throwing `InvalidArgumentException` (unparsable right) -> the same 403, never 200/500.
- **Order:** behind `RequireSession` (guest -> 401 there, not here). Not a guard: never satisfies `requireSessionGuard`. A path placeholder `{jardisRights}` cannot grant a right.

## REVOKE SESSION — `RevokeSessionOnSuccess` (PSR-15, per route, since 3.1.0)
Logout / tenant switch: the session of the request is revoked once the route answered successfully. Orchestrator: handler first -> `ReadRequestAttribute` (`RequestAttribute::SESSION`) -> injected closure -> response unchanged.
```php
use JardisCore\App\Middleware\RevokeSessionOnSuccess;

$routes->middleware($requireSession);
$routes->post('/logout', $logout, new RevokeSessionOnSuccess($sessionManager->invalidateByReference(...)));
```
- **Revokes** only for status `2xx` AND a non-empty string session reference: `$revoke($reference)` once. Not `2xx` or no reference -> nothing is revoked.
- **No swallowing:** an exception from `$revoke` leaves the middleware (error boundary). The response is never changed.
- **Effect:** at the next refresh (the refresh token fails); the access token already issued stays valid until its `exp`.
- **Order:** behind `RequireSession` (sets the reference). Not a guard: never satisfies `requireSessionGuard`.

## SYSTEM RUN — `SignedInRun` (since 3.2.0)
A system run (CLI, job) gets its rights like a user: sign in -> token -> switch tenant -> token -> target with `Authorization: Bearer`, every stage through `App::handle()` (so `RequireSession` + `RequireRight` judge it). Orchestrator over `Handler\System\BuildSystemRequest`, `BuildSwitchBody`, `ReadAccessToken`, `AuthenticateToken`.
```php
$run = new SignedInRun($app, $authenticator, Credential::token(...), new SessionDoor('POST', '/sign-in'), new SessionDoor('POST', '/switch-tenant'), 'tenantSlug');
$response = $run->handle('acme', '{"secret":"..."}', 'GET', '/orders');
```
- **Switch body:** `{"<tenantField>": "<tenant>"}` with the sign-in token as Bearer. Target answer is returned unchanged (also 4xx).
- **Token:** `data.access_token` of the session door's answer (RFC 6749 section 5.1: `access_token`, `token_type`, `expires_in`, `refresh_token`), read by `Handler\System\ReadAccessToken` (since 3.3.0; `FIELD` constant): non-empty string AND accepted by the authenticator, else `SystemRunRefused`. Other fields (`refresh_token`) are never looked at. `ReadSessionToken` (the old probe over all strings of `data`) is `@deprecated` since 3.3.0, removed in 4.0.
- **`SystemRunRefused(reason, ?status)`:** empty tenant (before any request), sign-in/switch not `2xx`, no usable access token. No body in the message. No sign-out after the run.
