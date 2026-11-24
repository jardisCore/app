<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Directory;

use JardisSupport\Contract\Auth\GroupLookupInterface;

/**
 * Translator contract -> tenant question: the fallback group of ONE information kind per tenant, from
 * `fallbackGroupOf($kind, $tenant)`; `null` when the kind has none in that tenant.
 *
 * The form `(?string $tenant): ?int` is what a fallback-group closure of a bulk run expects (e.g. the argument of
 * `AclBackfill::run` of `jardissupport/acl`; this package does not know that package).
 */
final class FallbackGroupPerTenant
{
    public function __construct(
        private readonly GroupLookupInterface $groups,
        private readonly int $kind,
    ) {
    }

    public function __invoke(?string $tenant): ?int
    {
        return $this->groups->fallbackGroupOf($this->kind, $tenant);
    }
}
