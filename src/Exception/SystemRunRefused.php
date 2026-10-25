<?php

declare(strict_types=1);

namespace JardisCore\App\Exception;

/**
 * Thrown by {@see \JardisCore\App\SignedInRun} when a system run does not reach its target door: no tenant given,
 * a session door (sign in, switch tenant) that did not answer 2xx, or an answer from which no single session token
 * can be read. The target door itself never throws this - its answer (also 4xx) is returned unchanged.
 *
 * Carries the reason and, when a door answered, its status. Never carries the body of an answer: it may hold tokens.
 */
final class SystemRunRefused extends AppException
{
    public function __construct(
        public readonly string $reason,
        public readonly ?int $status = null,
    ) {
        parent::__construct(
            'System run refused: ' . $reason . ($status === null ? '' : ' (status ' . $status . ')'),
        );
    }
}
