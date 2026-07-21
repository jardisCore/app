<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command;

/**
 * Process DTO for ExternalCallServiceDemo.
 *
 * Readonly DTO for command/query operations.
 */
readonly class ExternalCallServiceDemo
{
    public function __construct(
        public string $note
    ) {
    }
}
