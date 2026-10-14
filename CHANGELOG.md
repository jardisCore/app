# Changelog

All notable changes to `jardiscore/app` are listed here. Versions only move forward (SemVer).

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
