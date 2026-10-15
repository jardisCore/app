# Changelog

All notable changes to `jardiscore/app` are listed here. Versions only move forward (SemVer).

## v1.7.0

`ApplySession` binds the role of the session to the request.

### Added

- Request attribute `RequestAttribute::ROLE` (`jardisRole`, contracts ^2.11): set by `ApplySession` when the authenticator's
  result is a `RoleAwareAuthResultInterface` (auth ^1.6, claim `role`) and `getRole()` is non-empty; otherwise the attribute is
  absent (never `null`, never `''`). It is removed first like the other session attributes, so a client can never inject it
  (e.g. via a path placeholder `{jardisRole}`). The role is an attribute, not a guard - `jardiscore/app` authorizes nothing.

### Changed

- `composer.json`: `jardissupport/contracts` `^2.11`; dev: `jardissupport/auth` `^1.6`.

### Tests

- `SessionSignedTokenTest`: token with claim `role: owner` -> `jardisRole = 'owner'`; without claim -> attribute absent;
  a client-supplied `jardisRole` is removed (no claim) or overwritten (claim).

## v1.6.0

Wiring out, contracts only. `jardiscore/app` no longer knows `jardissupport/auth` or `jardisadapter/dbconnection`;
`RequireSession` stays a PSR-15 class that talks to an `AuthenticatorInterface` and nothing else.

### Removed (breaking)

- `Middleware\MiddlewareFactory` and the standard name `session`.
- `Handler\Middleware\ResolveTokenStore`, `BuildSessionMiddleware`, `BuildProjectMiddleware`.
- `Handler\Session\IsAnonymous` and the constructor argument `anonymousPaths` of `RequireSession`.
  Anonymous is only `Route::$anonymous` now (`Routes::anonymous()`, `Routes::health()`); `RequireSession`
  reads the request attribute `DispatchAndRespond::ANONYMOUS_ATTRIBUTE`.
- `suggest` entries for `jardissupport/auth` and `jardisadapter/dbconnection` in `composer.json`.

### Changed

- `RequireSession::__construct(AuthenticatorInterface, Closure $credentialFor, ResponseFactoryInterface, StreamFactoryInterface, string $headerName = 'Authorization', string $scheme = 'Bearer')`.
  Behaviour otherwise unchanged (401 envelope, `WWW-Authenticate`, session attributes).
- The application builds the middleware itself; the Jardis builder generates `src/App/middleware.php`.
- Docs and the `core-app` skill describe the new wiring.

### Tests

- `SessionSignedTokenTest` (needs `jardissupport/auth ^1.5`): a protected route answers 200 with a signed access token without any
  database connection, 401 without a token and with a tampered token, anonymous routes pass.
- Tests of the removed classes are gone; anonymous-path tests use `Routes::anonymous()`.

### Migration

Replace `(new MiddlewareFactory($kernel))('session')` with
`new RequireSession(new SignedTokenAuthenticator($key), Credential::token(...), $psr17, $psr17)` and register former
`anonymousPaths` as `$routes->anonymous()->...` routes.

## v1.5.2 and earlier

See the GitHub releases.
