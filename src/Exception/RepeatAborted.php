<?php

declare(strict_types=1);

namespace JardisCore\App\Exception;

/**
 * Thrown by {@see \JardisCore\App\Handler\Repeat\RepeatWhileOpen} when a repeated call cannot be judged or does not
 * make progress: the number of open items did not fall below the one of the previous round, or the answer carries no
 * integer in the named field.
 *
 * Carries the reason (it names the next step: remove the cause in the call, start again) and, when the answer
 * carried one, the number of open items. Never carries the body of an answer.
 */
final class RepeatAborted extends AppException
{
    public function __construct(
        public readonly string $reason,
        public readonly ?int $open = null,
    ) {
        parent::__construct(
            'Repeat aborted: ' . $reason . ($open === null ? '' : ' (open: ' . $open . ')'),
        );
    }
}
