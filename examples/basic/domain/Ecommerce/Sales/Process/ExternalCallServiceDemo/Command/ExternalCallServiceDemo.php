<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command;

use DateTimeImmutable;

/**
 * Process DTO for ExternalCallServiceDemo.
 *
 * Readonly DTO for command/query operations.
 */
readonly class ExternalCallServiceDemo
{
    public function __construct(
        public string $note,
        public string $tenant,
        public string $user,
        public ?string $budget = null,
        public ?string $channel = null,
        public ?string $confirmationKey = null,
        public ?DateTimeImmutable $requestedOn = null
    ) {
    }
}
