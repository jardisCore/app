# Jardis App

![Build Status](https://github.com/jardisCore/app/actions/workflows/ci.yml/badge.svg)
[![Latest Version](https://img.shields.io/packagist/v/jardiscore/app.svg)](https://packagist.org/packages/jardiscore/app)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-777BB4.svg)](https://www.php.net/)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](phpstan.neon)
[![PSR-12](https://img.shields.io/badge/Code%20Style-PSR--12-blue.svg)](phpcs.xml)
[![PSR-7](https://img.shields.io/badge/PSR--7-HTTP%20Message-blue.svg)](https://www.php-fig.org/psr/psr-7/)
[![PSR-15](https://img.shields.io/badge/PSR--15-HTTP%20Handlers-blue.svg)](https://www.php-fig.org/psr/psr-15/)

> Part of **[Jardis](https://jardis.io)** — the Domain-Driven Design platform for PHP. You model your domain; Jardis generates the production-ready hexagonal code. `jardiscore/kernel` is the runtime the generated code runs on; this package is the HTTP delivery layer around it.

**The Request/Response-Handling offer for Jardis-generated domains.** FastRoute
behind an own, narrow interface, a PSR-15 middleware pipeline, one canonical
`DomainResponse` → PSR-7 mapper, and a thin bootstrap bridge around the
DomainKernel (`BuildDomainKernelFromEnv`) — nothing more.

**Staying is the expected default — leaving is the guaranteed freedom.** No
Jardis domain ever imports this package (a mechanically checkable structural
property, not a promise — see [Wall Freedom](#wall-freedom) below); a
third-party framework can answer the exact same client-facing envelope
contract this package implements, without depending on it at all (see
`examples/symfony-demo/`).

---

## Why Jardis App?

Between "an HTTP request arrives" and "`$domain->…->process(…)` gets called"
there was no Jardis offer — building a Jardis-backed HTTP API meant pulling
in Laravel or Symfony just for routing and response mapping. This package
closes that gap with a deliberately small core:

- **FastRoute, but swappable.** `nikic/fast-route` sits behind
  `Contract\RouterInterface` — an implementation detail, not a commitment.
- **PSR-15 pipeline.** Global and per-route middleware, Chain-of-Responsibility
  ordering (global wraps route-specific), deterministic within each group. Any
  PSR-15-conformant ecosystem package (auth, CORS, request-id, …) runs
  without an adapter.
- **One canonical mapper.** Every `DomainResponseInterface` — success or
  error, domain- or boundary-produced — becomes the same
  `{status, data, errors, meta}` JSON envelope
  (`vendor/jardissupport/contracts/docs/response-envelope.md`). No consumer
  builds this translation by hand again.
- **A thin bootstrap bridge.** Wraps the DomainKernel's own
  `BuildDomainKernelFromEnv` (ENV → `DomainKernel`) and the Builder-generated
  domain composition (`App/bootstrap.php`) with the one thing missing: an
  HTTP entry point around them.
- **A defined boundary-error contract.** 404/405 (with a spec-correct
  `Allow` header)/500 all answer in the same envelope as a domain error — a
  client never has to guess whether "404" came from the domain or from no
  route matching at all.
- **A Raw-Body invariant.** The request body is read from `php://input`
  exactly once, into a seekable stream; JSON parsing is lazy and never
  replaces it — `getBody()` stays byte-identical for the whole pipeline
  (the webhook-HMAC case).

## Wall Freedom

The "no domain depends on this package" guarantee is a structural property,
not an example: `tests/Acceptance/WallFreedomHarnessTest.php` checks every
vendored domain's composer dependencies and namespace imports for a
dependency on `jardiscore/app` — none exists, and none may. `examples/symfony-demo/`
illustrates what that freedom buys a consumer: the same generated domain,
answering the same envelope contract, behind Symfony instead of this
package's own router — with zero `jardiscore/app` import anywhere in that
demo's `composer.json` or code.

---

## Installation

```bash
composer require jardiscore/app
```

## Quickstart

See [`docs/getting-started.md`](docs/getting-started.md) for the full,
runnable `public/index.php` recipe (install → ENV/DomainKernel-Bootstrap → domain
registration → first route + handler → start → first request — under 15
minutes with a prepared environment, K6). It also covers API versioning
(M9), the `jardissupport/validation` growth path, and the N1 deployment
stellschrauben (body-size limits, Trusted-Proxy, `display_errors`).

## Examples

- [`examples/basic/`](examples/basic/) — the recipe above wired against a
  REAL Builder-generated domain (`Ecommerce`/`Sales`) over MySQL:
  `GET/POST/PATCH /orders`, the `422` rule-violation payload, 404/405/500
  boundary cases, and a real PSR-15 ecosystem middleware.
- [`examples/symfony-demo/`](examples/symfony-demo/) — the N3
  austauschbarkeits-illustration: the same fixture domain behind a Symfony
  front controller, fulfilling the same envelope contract, without
  importing this package.

---

## Requirements

- PHP `>=8.3`
- `jardiscore/kernel ^2.0`
- `jardissupport/contracts ^2.0`

## Related Packages

| Package | Purpose |
|---------|---------|
| `jardiscore/kernel` | The DomainKernel (`DomainKernel`) + ENV-Packer this package's bootstrap bridge builds on |
| `jardissupport/contracts` | `DomainResponseInterface`, `ResponseStatus`, the response-envelope contract doc |
| `jardissupport/validation` | Object-graph validation — the growth path beyond the handler's own trivial type coercion (F8) |

---

## Documentation

Full documentation, guides, and API reference:

**[docs.jardis.io/en/core/app](https://docs.jardis.io/en/core/app)**

---

## License

Jardis is open source under the [MIT License](LICENSE.md).
Free for any purpose — commercial or non-commercial.

---

*Jardis — Development with Passion*
*Built by [Headgent Development](https://headgent.com)*

<!-- BEGIN jardis/dev-skills README block — do not edit by hand -->
## AI-Assisted Development

This package ships with a skill for Claude Code, Cursor, Continue, and Aider. Install it in your consuming project:

```bash
composer require --dev jardis/dev-skills
```

More details: <https://docs.jardis.io/en/skills>
<!-- END jardis/dev-skills README block -->
