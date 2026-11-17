<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Request;

use JardisCore\App\Data\ShareTargetRequest;
use JardisSupport\Contract\Auth\GroupLookupInterface;

/**
 * Resolves the named share targets against the tenant through the contract: a person becomes her personal group
 * ({@see GroupLookupInterface::personalGroupOf()}), the named groups must all exist in the tenant
 * ({@see GroupLookupInterface::existingGroups()}, asked once and only when groups are named).
 *
 * - result: group id => level word, the words unchanged;
 * - `false` = not resolvable: the person has no personal group, or the answer misses a named group (a group of
 *   another tenant or an unknown one).
 *
 * Never rejects and never checks a word. A throwing lookup is not caught.
 */
final class LookUpShareTargets
{
    public function __construct(private readonly GroupLookupInterface $lookup)
    {
    }

    /**
     * @return array<int, string>|false
     */
    public function __invoke(ShareTargetRequest $named, ?string $tenant): array|false
    {
        $targets = $named->groups;

        if ($targets !== []) {
            $asked = array_keys($targets);

            if (array_diff($asked, $this->lookup->existingGroups($asked, $tenant)) !== []) {
                return false;
            }
        }

        if ($named->person !== null) {
            $personal = $this->lookup->personalGroupOf($named->person, $tenant);

            if ($personal === null) {
                return false;
            }

            $targets[$personal] = $named->personWord;
        }

        return $targets;
    }
}
