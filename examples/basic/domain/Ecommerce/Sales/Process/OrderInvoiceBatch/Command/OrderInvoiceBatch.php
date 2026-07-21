<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command;

use Ecommerce\Sales\Aggregate\Invoice\Command\AddInvoiceLine;
use Ecommerce\Sales\Aggregate\Invoice\Command\UpdateInvoice;
use Ecommerce\Sales\Aggregate\Order\Command\AddOrderItem;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder;

/**
 * Process DTO for OrderInvoiceBatch.
 *
 * Readonly DTO for command/query operations.
 */
readonly class OrderInvoiceBatch
{
    public function __construct(
        /** @param array<AddOrderItem|AddInvoiceLine> $lineCommands */
        public array $lineCommands,
        public UpdateOrder|UpdateInvoice $primaryCommand,
        public string $requestedBy,
        public string $note = ''
    ) {
    }
}
