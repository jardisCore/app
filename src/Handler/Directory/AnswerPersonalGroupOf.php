<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Directory;

use Closure;

/**
 * Question (b) of the group directory: the personal group of a person. Hands the question to the injected door
 * call and returns its answer unchanged.
 */
final class AnswerPersonalGroupOf
{
    /**
     * @param Closure(string, ?string): ?int $door
     */
    public function __construct(private readonly Closure $door)
    {
    }

    public function __invoke(string $subjectId, ?string $tenant): ?int
    {
        return ($this->door)($subjectId, $tenant);
    }
}
