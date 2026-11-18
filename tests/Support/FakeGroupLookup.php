<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use JardisSupport\Contract\Auth\GroupLookupInterface;
use JardisSupport\Contract\Auth\SubjectGroupsInterface;
use RuntimeException;

/**
 * Test fake for the GroupLookupInterface port: a fixed answer for `groupsOf()`, every call recorded.
 * A subject id of `boom` throws. `personalGroupOf()` and `existingGroups()` are recorded as well; by default they
 * answer like a directory without a tenant boundary (the one personal group, every asked group exists). The
 * optional arguments make the directory strict: known groups, personal groups per subject id, one single tenant.
 * `fallbackGroupOf()` is recorded as well and answers from the optional map kind => group (default: no kind has a
 * fallback group); with `$fallbackThrows` it throws.
 */
final class FakeGroupLookup implements GroupLookupInterface
{
    /** @var list<array{0: string, 1: ?string}> */
    public array $calls = [];

    /** @var list<array{0: string, 1: ?string}> */
    public array $personalCalls = [];

    /** @var list<array{0: list<int>, 1: ?string}> */
    public array $existingCalls = [];

    /** @var list<array{0: int, 1: ?string}> */
    public array $fallbackCalls = [];

    /**
     * @param list<int> $groups
     * @param list<int>|null $knownGroups groups that exist in `$onlyTenant`; null = every asked group exists
     * @param array<array-key, int>|null $personalGroups subject id => personal group; null = every subject has `$personal`
     * @param string|null $onlyTenant the one tenant the directory knows; every other tenant has no group and no person
     * @param array<int, int|null> $fallbackGroups information kind => fallback group; a kind not listed has none
     * @param bool $fallbackThrows `fallbackGroupOf()` throws, like an unreachable group store
     */
    public function __construct(
        private readonly array $groups = [],
        private readonly ?int $personal = null,
        private readonly ?array $knownGroups = null,
        private readonly ?array $personalGroups = null,
        private readonly ?string $onlyTenant = null,
        private readonly array $fallbackGroups = [],
        private readonly bool $fallbackThrows = false,
    ) {
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
        $this->personalCalls[] = [$subjectId, $tenant];

        if ($this->onlyTenant !== null && $tenant !== $this->onlyTenant) {
            return null;
        }

        return $this->personalGroups === null ? $this->personal : ($this->personalGroups[$subjectId] ?? null);
    }

    public function existingGroups(array $groups, ?string $tenant): array
    {
        $this->existingCalls[] = [$groups, $tenant];

        if ($this->onlyTenant !== null && $tenant !== $this->onlyTenant) {
            return [];
        }

        return $this->knownGroups === null
            ? $groups
            : array_values(array_filter($groups, fn (int $group): bool => in_array($group, $this->knownGroups, true)));
    }

    public function fallbackGroupOf(int $kind, ?string $tenant): ?int
    {
        $this->fallbackCalls[] = [$kind, $tenant];

        if ($this->fallbackThrows) {
            throw new RuntimeException('fallback store unreachable');
        }

        return $this->fallbackGroups[$kind] ?? null;
    }
}
