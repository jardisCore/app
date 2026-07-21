<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Error;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Logs a Throwable at PSR-3 `error` level (F9) with the full error as
 * context. Falls back to `error_log` when no logger is injected, or when
 * the logger itself throws - a broken logging backend never affects the
 * 500 response HandleThrowable returns to the client.
 */
final class LogThrowable
{
    public function __construct(private readonly ?LoggerInterface $logger)
    {
    }

    public function __invoke(Throwable $throwable): void
    {
        if ($this->logger === null) {
            $this->fallback($throwable);

            return;
        }

        try {
            $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
        } catch (Throwable) {
            $this->fallback($throwable);
        }
    }

    private function fallback(Throwable $throwable): void
    {
        error_log(sprintf(
            '%s: %s in %s:%d',
            $throwable::class,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
        ));
    }
}
