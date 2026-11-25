## Middleware: when and where

**Is it middleware? Three questions, all "yes":** (1) Does it apply equally to all or many routes, independent of any aggregate? (2) Does it need only the request/response, not the domain model? (3) Does it run before or after the route, never in the middle? Otherwise it is a process node / Rule / Closure. "May this user see this project" is domain logic (Rule); "is there a valid session" is middleware.

**Fixed, not switchable** (`src/App.php`, `Handler/Routing/DispatchAndRespond.php`): `HandleThrowable` (outermost, any throwable incl. from middleware -> 500 envelope) -> router (404/405 answered before any middleware) -> path attributes -> `ResolveVersion` (`X-Jardis-Version`, 400 before any middleware) -> middleware pipeline -> route handler (`ResolveRouteHandler`/`ResolveResponse`, `MapDomainResponse` maps `DomainResponseInterface`) -> `EmitResponse` in `run()`. `ParseJsonBody` is a helper handlers call, not a chain stage.

**Pluggable:** any PSR-15 `MiddlewareInterface`. `Routes::middleware($m)` = global; the verb methods (`get/post/put/patch/delete`) take trailing `MiddlewareInterface ...$middleware` = per route. There is no route-group API. Order (`Handler/Pipeline/RunMiddlewarePipeline.php`): `[...global, ...route]`, outer first, registration order within each group.

**Delivered:** `Middleware\RequireSession` - reads `Authorization: Bearer <token>`, authenticates through an `AuthenticatorInterface`, else 401 envelope + `WWW-Authenticate: Bearer`. Sets `RequestAttribute::SUBJECT`, `TENANT` (absent without tenant), `ROLE` (only for a `RoleAwareAuthResultInterface` with a non-empty role, else absent), `RIGHTS` (only for a `RightsAwareAuthResultInterface` with a non-empty list, else absent), `SUBJECT_ID`, `SESSION` (last two only for a session-aware result); client-supplied values are overwritten/removed. **Also delivered:** `Middleware\RequireRequestAttribute` - per route, behind the session door: each named attribute must be a non-empty string, else 403 envelope `errors {message, messageKey: 'request.attributeMissing', context: {attribute}}` (first missing name wins); presence only, authorizes nothing, not a session guard. Anonymous: routes registered by `Routes::health()` or `Routes::anonymous()` (`Route::$anonymous`); there is no path-pattern list.

**Where (app template):** the pipeline is the **project setting `middleware`** (Builder, `project.json`). `core/app` ships no wiring: the Builder generator emits `src/App/middleware.php` (`return static function (DomainKernelInterface $kernel): array`, regenerated on every build), which builds the PSR-15 instances itself, e.g.
```php
return static function (DomainKernelInterface $kernel): array {
    $psr17 = new Psr17Factory();
    $key = base64_decode((string) $_ENV['AUTH_TOKEN_KEY'], true);   // raw 32 bytes

    return [new RequireSession(new SignedTokenAuthenticator($key), Credential::token(...), $psr17, $psr17)];
};
```
The once-written `src/App/bootstrap.php` loads it into the `'middleware'` entry of the app config, and the template's `public/index.php` registers every entry of `$app['middleware']` via `$routes->middleware()` (strictly checked: `list<MiddlewareInterface>`, else 500 envelope, never a stack trace). The template sets `new AppConfig(requireSessionGuard: true)`, so a non-anonymous route without a session guard fails at start. A hand-written route that needs no session goes through `$routes->anonymous()->get(...)` in `public/index.php`; the session middleware must stand before any middleware that reads session attributes.

**Guarantees:** `Routes::health()` is always anonymous and never mounted under `$prefix`; 404, 405 and the version check (`X-Jardis-Version`) are answered before the pipeline runs.

**Boundary:** retrofitting a standard middleware = a PSR-15 class in `jardisCore/app` plus one line in the generated `middleware.php`; a project-specific middleware = an own PSR-15 class in the project, its FQCN named in the project setting. Not a patch of the template's `index.php`. A generally useful one (CORS, request id/audit, rate limit, tenant context) is an issue in `jardisCore/app`, not project code imitating the package.

## SESSION — `RequireSession` (PSR-15, global or per route)
Binds a request to a verified session or rejects it. Depends only on the ports of `jardissupport/contracts` ^2.13
(`AuthenticatorInterface`, `CredentialInterface`, `TenantAwareAuthResultInterface`, `RequestAttribute`) — NOT on `support/auth`.
```php
use JardisCore\App\Middleware\RequireSession;
use JardisSupport\Auth\Data\Credential;      // support/auth (the app requires it, not core/app)
use JardisSupport\Auth\SignedTokenAuthenticator; // access tokens of SessionManager (auth ^1.5): signature + exp, no store

$routes->middleware(new RequireSession(
    new SignedTokenAuthenticator($accessTokenKey), // raw 32-byte key
    Credential::token(...),                     // Closure(string): CredentialInterface
    $psr17, $psr17,                             // response + stream factory (the 401 uses BuildErrorResponse)
));
$routes->anonymous()->post('/auth/login', $login); // routes that need no session
$routes->health('/health');                     // anonymous automatically
```
- **Token:** `Authorization: Bearer <token>` (exactly one header line, scheme case-insensitive, no whitespace in the token).
  `headerName`/`scheme` configurable; `scheme: ''` = the whole header value is the token.
- **401:** `{status:401, data:{}, errors:{message:"Authentication required."}, meta:{}}` + `WWW-Authenticate: Bearer`; missing, malformed,
  unknown, expired, revoked token and a "successful" result without subject are answered identically (reason stays internal).
  The handler is never called. A `Throwable` from the authenticator (token store down) is NOT a 401 but the generic 500.
- **Session:** valid → request attribute `RequestAttribute::SUBJECT` (`jardisSubject`) and, if the result is `TenantAwareAuthResultInterface`
  with a non-empty tenant, `RequestAttribute::TENANT` (`jardisTenant`); no tenant → the attribute is ABSENT (never null/''). Pre-existing
  attributes of these names (e.g. a path placeholder `{jardisSubject}`) are always overwritten/removed, also on anonymous paths.
  If the result is a `RoleAwareAuthResultInterface` (contracts ^2.11, auth ^1.6) with a non-empty role, `RequestAttribute::ROLE` (`jardisRole`) is set too; else ABSENT (never null/''). It is an attribute, not a guard - core/app authorizes nothing, no new middleware. Same forgery strip.
  If the result is a `RightsAwareAuthResultInterface` (contracts ^2.16, auth ^1.8) with a non-empty `getRights()` (`list<string>`, claim `SessionClaim::RIGHTS`), `RequestAttribute::RIGHTS` (`jardisRights`) is set too; empty list / no claim / plain result -> ABSENT (never `[]`/null). A client value (path placeholder `{jardisRights}`) is always removed first. Read it with `ReadRequestAttributeList`.
  If the result is a `SessionAwareAuthResultInterface` (contracts ^2.9, auth ^1.3) it additionally sets `RequestAttribute::SUBJECT_ID`
  (`jardisSubjectId`, bare id without `user:` prefix) and `RequestAttribute::SESSION` (`jardisSession`, reference that revokes exactly this
  session); empty/null or a plain tenant-aware result → both ABSENT. Same forgery strip as SUBJECT/TENANT.
  Recipe "revoke own session": `$sessionManager->invalidateByReference($request->getAttribute(RequestAttribute::SESSION))`.
- **Where it runs:** only on a matched route (inside the pipeline) → unknown path stays 404, wrong method 405. `Routes::health()` and `Routes::anonymous()` routes carry
  `Route::$anonymous` → request attribute `DispatchAndRespond::ANONYMOUS_ATTRIBUTE` (`jardisAnonymousRoute`, strict bool, always written by
  the dispatcher, compare with `=== true`). 
- **Per route:** `$routes->get('/x', $handler, $requireSession)` guards only that route (global = guard everything except anonymous).
- **`Contract\SessionGuardInterface`:** method-less marker (`extends MiddlewareInterface`), implemented by `RequireSession`. The start-up check only tests `instanceof`; a decorator around the guard does not count unless it implements the marker.
- **Switch:** `new AppConfig(requireSessionGuard: true)` makes `new App(...)` throw `LogicException` listing `METHOD path` of every non-anonymous route without a guard in `[...global, ...route]`. Default `false`.
