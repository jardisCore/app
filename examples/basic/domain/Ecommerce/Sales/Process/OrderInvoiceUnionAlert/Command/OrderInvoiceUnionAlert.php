<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command;

use Ecommerce\Sales\Model\Invoice\Command\RemoveInvoice;
use Ecommerce\Sales\Model\Order\Command\RemoveOrder;

/**
 * Process DTO for OrderInvoiceUnionAlert.
 *
 * Readonly DTO for command/query operations.
 */
readonly class OrderInvoiceUnionAlert
{
    public function __construct(
        public RemoveOrder|RemoveInvoice $subjectRemoval
    ) {
    }
}
