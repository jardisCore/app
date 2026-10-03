# jardiscore/app

HTTP-delivery layer for Jardis-generated domains: FastRoute behind an own `Contract\RouterInterface`, a PSR-15 middleware pipeline, one canonical `DomainResponse` → PSR-7 mapper (`{status, data, errors, meta}` envelope), and a thin bootstrap bridge around the DomainKernel (`BuildDomainKernelFromEnv`). No Jardis domain ever imports this package (Wall Freedom) — a third-party framework can answer the same envelope contract without it.

## Usage essentials

- **Main classes (`JardisCore\App`):** `Routes` (registration: `get/post/put/patch/delete`, `middleware()`, `health()`), `Router` (dispatch, FastRoute never leaks past it), `App` (orchestrator: pure `handle(ServerRequestInterface)`, impure `run()`), `Config\AppConfig` (`debug` flag, injected by the bootstrap — never reads ENV itself).
- **Bootstrap recipe:** `BuildDomainKernelFromEnv` → `DomainKernel` → generated domain(s) `new {Domain}($kernel)` (not part of this package) → `Routes` + handlers → `App` → `run()`. Full runnable `public/index.php`: `docs/getting-started.md`.
- **Handlers return `DomainResponseInterface` or a PSR-7 `ResponseInterface`** — anything else throws `UnresolvableHandlerResult` and ends in the generic 500.
- **One envelope for every answer:** `MapDomainResponse` maps each `DomainResponseInterface` (`ResponseStatus` 1:1 to the HTTP code, e.g. `RuleViolation` = 422); 204 has no body; `BuildErrorResponse` is the only place assembling the envelope, also for 404 / 405 (with `Allow` header) / 500. `getEvents()` is never part of the client-facing envelope.
- **Errors:** `HandleThrowable` is the outermost boundary — `InvalidJsonBody` → 400, any other `Throwable` → generic 500 (details only with `AppConfig::$debug === true`), the full exception always goes to the PSR-3 logger.
- **Request body:** read from `php://input` exactly once into a seekable stream; `ParseJsonBody` is lazy and leaves `getBody()` byte-identical (webhook-HMAC case).
- **Don't:** read ENV inside `AppConfig`/handlers (inject `debug` from the bootstrap); hardwire PSR-17 factories inside handler/business code (receive the interfaces; `Nyholm\Psr7\Factory\Psr17Factory` is only `App`'s injectable default); expect this package to solve body-size limits, Trusted-Proxy/`X-Forwarded-*` or `display_errors=Off` (webserver/FPM/proxy responsibility); expect OPTIONS to be added automatically; import this package from a domain.
- **Skill:** `core-app` (`.claude/skills/core-app/SKILL.md`) — consult it before using the API.

## Full reference

https://docs.jardis.io/en/core/app
