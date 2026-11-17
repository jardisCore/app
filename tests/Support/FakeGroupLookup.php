<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use JardisSupport\Contract\Auth\GroupLookupInterface;
use JardisSupport\Contract\Auth\SubjectGroupsInterface;
use RuntimeException;

/**
 * Test fake for the GroupLookupInterface port: a fixed answer for `groupsOf()`, every call recorded.
 * A subject id of `boom` throws. The other three questions are not used by the app middleware and answer
 * the neutral value.
 */
final class FakeGroupLookup implements GroupLookupInterface
{
    /** @var list<array{0: string, 1: ?string}> */
    public array $calls = [];

    /**
     * @param list<int> $groups
     */
    public function __construct(private readonly array $groups = [], private readonly ?int $personal = null)
    {
    }

    public function groupsOf(string $subjectId, ?string $tenant): SubjectGroupsInterface
    {
        $this->calls[] = [$subjectId, $tenant];

        if ($subjectId === 'boom') {
            throw new RuntimeException('group store unreachable');
        }

        return new class ($this->groups, $this->personal) implements SubjectGroupsInterface {
            /**
             * @param list<int> $groups
             */
            public function __construct(private readonly array $groups, private readonly ?int $personal)
            {
            }

            public function getGroups(): array
            {
                return $this->groups;
            }

            public function getPersonalGroup(): ?int
            {
                return $this->personal;
            }
        };
    }

    public function personalGroupOf(string $subjectId, ?string $tenant): ?int
    {
        return $this->personal;
    }

    public function existingGroups(array $groups, ?string $tenant): array
    {
        return $groups;
    }

    public function fallbackGroupOf(int $kind, ?string $tenant): ?int
    {
        return null;
    }
}
