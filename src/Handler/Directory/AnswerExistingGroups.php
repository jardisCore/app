<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Directory;

use Closure;

/**
 * Question (c) of the group directory: which of the given groups exist in the tenant. Hands the question to the
 * injected door call and returns its answer unchanged.
 */
final class AnswerExistingGroups
{
    /**
     * @param Closure(list<int>, ?string): list<int> $door
     */
    public function __construct(private readonly Closure $door)
    {
    }

    /**
     * @param list<int> $groups
     * @return list<int>
     */
    public function __invoke(array $groups, ?string $tenant): array
    {
        return ($this->door)($groups, $tenant);
    }
}
