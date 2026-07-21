<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Test fake: a PSR-3 logger that always throws - used to prove
 * HandleThrowable/LogThrowable fall back to `error_log` when the logging
 * backend itself is broken (F9c), without affecting the 500 response.
 */
final class ThrowingLogger extends AbstractLogger
{
    /**
     * @param array<string, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        throw new RuntimeException('logger exploded');
    }
}
