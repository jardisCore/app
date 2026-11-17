<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Directory;

use Closure;
use JardisCore\App\Data\SubjectGroups;
use JardisSupport\Contract\Auth\SubjectGroupsInterface;

/**
 * Question (a) of the group directory: the groups of a person. Hands the question to the injected door call and
 * wraps its raw answer into {@see SubjectGroups}; no logic of its own.
 */
final class AnswerGroupsOf
{
    /**
     * @param Closure(string, ?string): array{groups: list<int>, personal: ?int} $door
     *        `groups` holds the personal group, `personal` is its id or `null`
     */
    public function __construct(private readonly Closure $door)
    {
    }

    public function __invoke(string $subjectId, ?string $tenant): SubjectGroupsInterface
    {
        $answer = ($this->door)($subjectId, $tenant);

        return new SubjectGroups($answer['groups'], $answer['personal']);
    }
}
