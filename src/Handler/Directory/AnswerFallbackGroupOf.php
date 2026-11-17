<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Directory;

use Closure;

/**
 * Question (d) of the group directory: the fallback group of an information kind. Hands the question to the
 * injected door call and returns its answer unchanged.
 */
final class AnswerFallbackGroupOf
{
    /**
     * @param Closure(int, ?string): ?int $door
     */
    public function __construct(private readonly Closure $door)
    {
    }

    public function __invoke(int $kind, ?string $tenant): ?int
    {
        return ($this->door)($kind, $tenant);
    }
}
