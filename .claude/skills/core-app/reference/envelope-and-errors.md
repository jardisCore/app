## ENVELOPE CONTRACT — `{status, data, errors, meta}`
Reference: `jardissupport/contracts` → `docs/response-envelope.md`. `ResponseStatus` (11 cases, `jardissupport/contracts`,
`JardisSupport\Contract\Kernel\ResponseStatus`) maps 1:1 to the HTTP code:

| Case | HTTP |
|---|---|
| `Success` | 200 |
| `Created` | 201 |
| `NoContent` | 204 |
| `ValidationError` | 400 |
| `Unauthorized` | 401 |
| `Forbidden` | 403 |
| `NotFound` | 404 |
| `MethodNotAllowed` | 405 |
| `Conflict` | 409 |
| `RuleViolation` | 422 |
| `InternalError` | 500 |

- **204 (`NoContent`)** is special-cased by `MapDomainResponse`: no body at all, no `Content-Type` header — a
  bare empty response, never the envelope builder.
- **Empty `data`/`errors`/`meta`** are coerced to a JSON object (`{}` via `stdClass`), never `[]`, by
  `BuildErrorResponse`.
- **405** carries an RFC-7231 `Allow` header (`, `-joined allowed methods) — the only case that populates
  `BuildErrorResponse`'s 5th (`allowedMethods`) parameter.
- **422 (`RuleViolation`)** — `getData()` is serialized completely unchanged under `data`; for a Rules-Layer
  violation this is typically `{rule, messageKey, context}`, but the mapper performs no reshaping.
- `getEvents()` is deliberately NOT part of the client-facing envelope — only 4 top-level keys ever appear.

## ERROR CONTRACT — `HandleThrowable`
- `Exception\InvalidJsonBody` → 400, envelope `errors.message` = the exception's own (non-sensitive) message.
- No exception type is answered 403 (since 3.0.0; the access-denied branch of the boundary is gone): the right of a route is checked by `RequireRight`. An exception that carries a message key and a context but no known type is an unknown exception -> 500.
- Any other `Throwable` → 500, **generic** body (no message/class/trace) unless `AppConfig::$debug === true`
  (then `errors.message`/`errors.exception`/`errors.trace` are added). The full exception always goes to the
  injected PSR-3 logger at `error` level regardless of `$debug` — `LogThrowable` falls back to `error_log()`
  when the logger is `null` or itself throws; the 500 response is unaffected either way.

## E13/E14 — auto-HEAD, OPTIONS
- **Auto-HEAD (E13):** `BuildDispatcher` registers `HEAD` automatically for every `GET` route unless an
  explicit `HEAD` route already exists for the same path — no double registration. `EmitResponse` then
  suppresses the body for a `HEAD` request but still sends `Content-Length`.
- **OPTIONS is never auto-added (E14):** an unregistered `OPTIONS` on a path with no routes at all → 404; on a
  path registered for other methods → 405 (`OPTIONS` absent from the `Allow` list) unless the app explicitly
  registers an `OPTIONS` route itself.
