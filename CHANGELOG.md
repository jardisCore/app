# Changelog

All notable changes to `jardiscore/app` are listed here. Versions only move forward (SemVer).

## [2.1.1] - 2026-10-23

The example runs on `jardissupport/workflow` 5 and `jardissupport/response` 2; `src/` is unchanged.

### Changed

- `composer.json`: `jardissupport/response` `^1.5` -> `^1.5 || ^2.0` (the package accepts response 2.0, where `CheckActor` is gone; `src/` uses neither);
  `jardissupport/workflow` (dev) `^3.0` -> `^5.0`.
- `examples/basic/domain/Ecommerce` re-copied from the current Builder generate: the process door no longer hands an actor to `workflow` (the actor is
  `$this->actor()` of the domain context), and `Ecommerce::sales()` takes the actor of the run. `ExampleAppFactory` passes `EcommerceActor::system('example-app')`.

## [2.1.0] - 2026-10-21

The rights of the session reach the request, and a denied access is answered `403`.

### Added

- `Handler\Session\ApplySession` (behind `Middleware\RequireSession`): the request attribute `RequestAttribute::RIGHTS` (`jardisRights`) is removed
  first (a client value can never arrive) and set only when the result is a `RightsAwareAuthResultInterface` with a non-empty list; otherwise it is
  absent (never `[]`, never `null`) - the pattern of `ROLE`.
- `Handler\Request\ReadRequestAttributeList`: `__invoke(ServerRequestInterface $request, string $name): ?array` - a `list<string>`; attribute absent -> `[]`;
  present but not a list of strings (a string, a list with a non-string element, an associative array) -> `null` ("unusable"); never an exception.
- `Handler\Error\HandleThrowable` answers `JardisSupport\Contract\Kernel\AccessDeniedExceptionInterface` (before the 500 branch) with the canonical
  403 envelope via `BuildErrorResponse`: `data` `{@type: "forbidden", context, messageKey}` from the exception (an empty `context` encodes as `{}`),
  `errors` and `meta` empty; the denial is not logged. `meta` stays `{}` here - the same denial thrown inside the domain door (`response`
  `MapThrowable`) carries the `meta` of the run.

### Changed

- `composer.json`: `jardissupport/contracts` `^2.15` -> `^2.16`, `jardissupport/response` `^1.4` -> `^1.5`, `jardissupport/auth` (dev) `^1.6` -> `^1.8`.

### Tests

- `ReadRequestAttributeListTest` (5 cases), rights cases in `SessionSignedTokenTest` (real `SessionManager` token) and `RequireSessionTest`, 403 cases in `HandleThrowableTest`.

## [2.0.0]

Package cut (separation of concerns, decision Rolf 2026-10-21): `app` is the HTTP delivery. What needs no PSR-7 and no router
- the type check of values and the 400 answer of the request mapper - moved to `jardissupport/response` 1.4.0. What hangs on the
HTTP request stays, and three small HTTP helpers arrive.

### Breaking

- REMOVED (moved to `jardissupport/response` ^1.4, same behaviour, new namespace - no alias left behind):

| Removed in `jardiscore/app` (`JardisCore\App\...`) | Now in `jardissupport/response` (`JardisSupport\Response\...`) |
|---|---|
| `Handler\Request\ReadRequired` | `Handler\Request\ReadRequired` |
| `Handler\Request\ReadBodyNullable` | `Handler\Request\ReadBodyNullable` |
| `Handler\Request\ReadTransportOptional` | `Handler\Request\ReadTransportOptional` |
| `Handler\Request\ReadTransportList` | `Handler\Request\ReadTransportList` |
| `Handler\Request\FindUnknownKeys` | `Handler\Request\FindUnknownKeys` |
| `Handler\Request\RebaseFindings` | `Handler\Request\RebaseFindings` |
| `Handler\Request\BuildRequestFindings` | `Handler\Request\BuildRequestFindings` |
| `Handler\Request\Accept\{TransportStr, TransportInt, TransportFloat, TransportDecimal, TransportBool, TransportDate, TransportDateTime, TransportTime, BodyStr, BodyInt, BodyFloat, BodyBool, BodyDate, BodyDateTime, BodyTime}` (15) | `Handler\Request\Accept\` (same names) |
| `Handler\Response\AnswerInvalidRequest` | `Handler\Answer\AnswerInvalidRequest` |

- The constructors changed with the move: the calendar accepts (`TransportDate|DateTime|Time`, `BodyDate|DateTime|Time`) take a
  `ValueValidatorInterface`; `AnswerInvalidRequest` takes a PSR-20 `ClockInterface` (no `SystemClock` default any more). See the
  `jardissupport/response` 1.4 README.
- `composer.json`: `jardissupport/response` `^1.2` -> `^1.4`; `jardissupport/validation` and `jardissupport/scheduling` leave `require`
  (no class in `src/` uses them any more) and stay in `require-dev` for the example domain and its tests.
- Kept unchanged: `ParseJsonBody`, `CreateServerRequest`, `ResolveVersion`, `InvalidJsonBody`.

### Added

- `Handler\Request\ReadRequestAttribute`: `__invoke(ServerRequestInterface $request, string $name): string` - the request attribute
  as a string, absent / not a string -> `''`.
- `Handler\Request\ReadRequestAttributeStrict`: same signature - absent / not a string / empty -> `Exception\RequestAttributeMissing`
  (extends `AppException` = `RuntimeException`, message `Request attribute <name> is not set - the scoped route needs the session middleware.`);
  the error boundary answers it as the generic 500.
- `Handler\Response\AnswerUnsupportedRequest`: `__invoke(list<string> $properties): DomainResponseInterface` - `DomainResponse`
  status `InternalError` (500), `data` `[]`, `errors` `['routes' => ['Request property not buildable by the generated adapter: <a, b>']]`.
- All three are creatable without constructor arguments. Wire behaviour equals the helpers the generated `routes.php` carried
  (`$scopeAttr`, `$scopeAttrStrict`, `$unsupported`).

### Tests

- One integration test per new closure (valid, absent, not a string, empty; the 500 wire bytes through `HandleThrowable` /
  `MapDomainResponse`); the tests, the fixture `tests/Fixture/envelope/route/s2-400.json` and the examples of the removed classes are gone.

## v1.9.0

The request mapper: type check of the body, query and path values and the 400 answer, as closures of this package
instead of helpers every generated `routes.php` assembles itself.

### Added

- `Handler\Request\Accept\*` (15 final closures, one `__invoke(mixed): ?T` each, `null` = refused): `TransportStr|Int|Float|
  Decimal|Bool|Date|DateTime|Time` for path/query values (a string end to end, validated first, coerced second) and
  `BodyStr|Int|Float|Bool|Date|DateTime|Time` for the decoded JSON body (strict JSON type; the temporal ones return a
  `DateTimeImmutable`, `BodyDateTime` normalised to UTC). The calendar forms are judged by `jardissupport/validation`
  (`DateTime::dateOnly()` / `rfc3339()` / `H:i:s`); `TransportDecimal` takes the anchored pattern of the column as an
  optional constructor argument.
- `Handler\Request\{ReadRequired, ReadTransportOptional, ReadTransportList, ReadBodyNullable}`:
  read one value through an accept closure and COLLECT the finding (`missing`, `type`) into the by-reference list of the
  request, with a fallback (required) or `null` (optional/nullable) standing in; `FindUnknownKeys` (`unknown`, after the known
  findings, body order) and `RebaseFindings` (`<list>[1].<field>` for nested payloads).
- `Handler\Request\BuildRequestFindings`: the collected findings as `InputFindings` of `jardissupport/response` (texts and keys
  `validation.<reason>`, the whole vocabulary - `missing`, `type`, `unknown`, fallback - from `DescribeFieldFinding` of `response`;
  the closure only chains it). Needs the `response` release that knows `type` and `unknown`.
- `Handler\Response\AnswerInvalidRequest`: the one 400 answer of a request that does not fit (`AnswerFindings` of
  `jardissupport/response`, `TransformResponse` with a PSR-20 clock, default `SystemClock`); bytes as the 400 of the generated routes.
- All new closures are creatable without constructor arguments (`Factory::get()` / `handle()`).

### Changed

- `DispatchAndRespond` builds the `data` of the 400 for an unknown `X-Jardis-Version` with `ValidationPayload` and
  `BuildResponseData` of `jardissupport/response` instead of an array literal; the bytes are unchanged.
- `composer.json`: new `require` `jardissupport/response ^1.2`, `jardissupport/scheduling ^1.2` (`SystemClock`),
  `jardissupport/validation ^1.5` (moved from `require-dev`); `jardissupport/contracts` `^2.11` -> `^2.15`.

### Tests

- One table over the 15 accept closures (valid, refused), the readers per type (valid, missing, invalid), and the 400 bytes
  against `tests/Fixture/envelope/route/s2-400.json` (copy of the Builder's route wire contract T1 S2, pinned by SHA-256).

## v1.8.1

The example domain follows the current generat and `jardissupport/workflow` 3.0. No change to `src/`.

### Changed

- `examples/basic/domain/Ecommerce` is rebuilt from the Builder's current generat (no `Ecommerce/Response/` any more: the
  response classes come from `jardissupport/workflow`); `ExampleApp\RegisterOrderRoutes` answers `POST /orders` from the process
  envelope and reloads the order through the read facade (flat `data` with `@type`, decimals as strings).
- `jardissupport/workflow` is `^3.0`: in the root `composer.json` under `require-dev`, in `examples/symfony-demo/composer.json` under `require`.

### Tests

- The `DomainResponse` fake and the acceptance, mapper and route tests use the flat `data` of contracts 2.12 / workflow 3.0
  (`@type`, no map keyed by context name); the 422 and 400 forms follow the workflow wire contract.

## v1.8.0

`RequireRequestAttribute` answers 403 when a required request attribute is missing.

### Added

- `Middleware\RequireRequestAttribute` (final, PSR-15, `__construct(ResponseFactoryInterface, StreamFactoryInterface, string ...$names)`):
  for each name, in order, the request attribute must be a non-empty string; the first one that is not ends the request with
  `403` in the canonical envelope (`data {}`, `errors {message, messageKey: 'request.attributeMissing', context: {attribute}}`) and the
  handler is never called. Otherwise the request passes unchanged. An empty name list or an empty name throws `InvalidArgumentException`.
  It checks presence only (authorizes nothing) and is not a `SessionGuardInterface`, so it never satisfies `AppConfig::$requireSessionGuard`.

### Tests

- `RequireRequestAttributeTest`: real `App`/`Routes` with a global `RequireSession` and the check per route - 403 form, 200 with
  the attribute, 401 (not 403) without a token, first missing name wins, path-placeholder forgery stays 403, 404/405 unchanged,
  constructor guards, not a session guard.

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
