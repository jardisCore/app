<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Test fake: a PSR-3 logger that records every call instead of writing
 * anywhere - used to prove HandleThrowable logs the full error at `error`
 * level (F9).
 */
final class LoggerSpy extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /**
     * @param array<string, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
