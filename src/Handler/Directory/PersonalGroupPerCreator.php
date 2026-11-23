<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Directory;

use JardisSupport\Contract\Auth\GroupLookupInterface;

/**
 * Translator contract -> list question: asks `personalGroupOf` once per creator of a tenant and returns the groups
 * found as `creator => group`. A creator without a personal group is missing from the answer (never `null`).
 *
 * The form `(?string $tenant, list<string> $creators): array<string, int>` is what a creator-to-group closure of a
 * bulk run expects (e.g. the argument of `AclBackfill::run` of `jardissupport/acl`; this package does not know that
 * package). Note: PHP turns a numeric creator id such as `'42'` into the integer array key `42`.
 */
final class PersonalGroupPerCreator
{
    public function __construct(private readonly GroupLookupInterface $groups)
    {
    }

    /**
     * @param list<string> $creators bare creator identifiers
     * @return array<string, int>
     */
    public function __invoke(?string $tenant, array $creators): array
    {
        $found = [];
        foreach (array_unique($creators) as $creator) {
            $group = $this->groups->personalGroupOf($creator, $tenant);
            if ($group !== null) {
                $found[$creator] = $group;
            }
        }

        return $found;
    }
}
