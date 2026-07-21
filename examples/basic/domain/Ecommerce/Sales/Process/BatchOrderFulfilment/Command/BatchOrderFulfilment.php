<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command;

/**
 * Process DTO for BatchOrderFulfilment.
 *
 * Readonly DTO for command/query operations.
 */
readonly class BatchOrderFulfilment
{
    public function __construct(
        public string $invoiceRef
    ) {
    }
}
