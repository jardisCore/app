<?php

declare(strict_types=1);

namespace JardisCore\App\Data;

use JardisSupport\Contract\Auth\SubjectGroupsInterface;

/**
 * The groups of one person as {@see SubjectGroupsInterface}: a plain value, built by
 * {@see \JardisCore\App\Handler\Directory\AnswerGroupsOf} from the raw answer of the door.
 */
final readonly class SubjectGroups implements SubjectGroupsInterface
{
    /**
     * @param list<int> $groups all groups of the person, including the personal group
     */
    public function __construct(private array $groups, private ?int $personal)
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
}
