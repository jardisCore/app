<?php

declare(strict_types=1);

namespace JardisCore\App;

use Closure;
use JardisCore\App\Handler\Directory\AnswerExistingGroups;
use JardisCore\App\Handler\Directory\AnswerFallbackGroupOf;
use JardisCore\App\Handler\Directory\AnswerGroupsOf;
use JardisCore\App\Handler\Directory\AnswerPersonalGroupOf;
use JardisSupport\Contract\Auth\GroupLookupInterface;
use JardisSupport\Contract\Auth\SubjectGroupsInterface;

/**
 * The group directory of the access layer, answered through the doors of the domain: it fulfils
 * {@see GroupLookupInterface} over four injected calls with a raw answer, so `app` knows neither a door nor a store.
 *
 * Orchestrator: each question of the contract goes to ONE closure of its own under `Handler/Directory/`
 * ({@see AnswerGroupsOf}, {@see AnswerPersonalGroupOf}, {@see AnswerExistingGroups}, {@see AnswerFallbackGroupOf});
 * the adapter has no logic and no door answer of its own - what a door call throws, the caller gets.
 */
final class GroupDirectoryFromDoors implements GroupLookupInterface
{
    private readonly Closure $groupsOf;
    private readonly Closure $personalGroupOf;
    private readonly Closure $existingGroups;
    private readonly Closure $fallbackGroupOf;

    /**
     * @param Closure(string, ?string): array{groups: list<int>, personal: ?int} $groupsOf
     *        (a) subject id, tenant -> all groups including the personal one, and the personal group or `null`
     * @param Closure(string, ?string): ?int $personalGroupOf (b) subject id, tenant -> personal group or `null`
     * @param Closure(list<int>, ?string): list<int> $existingGroups (c) groups, tenant -> those that exist
     * @param Closure(int, ?string): ?int $fallbackGroupOf (d) information kind, tenant -> fallback group or `null`
     */
    public function __construct(
        Closure $groupsOf,
        Closure $personalGroupOf,
        Closure $existingGroups,
        Closure $fallbackGroupOf,
    ) {
        $this->groupsOf = (new AnswerGroupsOf($groupsOf))->__invoke(...);
        $this->personalGroupOf = (new AnswerPersonalGroupOf($personalGroupOf))->__invoke(...);
        $this->existingGroups = (new AnswerExistingGroups($existingGroups))->__invoke(...);
        $this->fallbackGroupOf = (new AnswerFallbackGroupOf($fallbackGroupOf))->__invoke(...);
    }

    public function groupsOf(string $subjectId, ?string $tenant): SubjectGroupsInterface
    {
        return ($this->groupsOf)($subjectId, $tenant);
    }

    public function personalGroupOf(string $subjectId, ?string $tenant): ?int
    {
        return ($this->personalGroupOf)($subjectId, $tenant);
    }

    public function existingGroups(array $groups, ?string $tenant): array
    {
        return ($this->existingGroups)($groups, $tenant);
    }

    public function fallbackGroupOf(int $kind, ?string $tenant): ?int
    {
        return ($this->fallbackGroupOf)($kind, $tenant);
    }
}
