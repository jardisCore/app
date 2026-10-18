<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Response;

/**
 * Response contract of the RemoveInvoice command.
 *
 * Carries the reference values the command handler writes into
 * DomainResponse::setData() — the aggregate's public identifier plus the
 * affected child reference. Never a state copy: the caller reads the new
 * state back through the read facade.
 */
class RemoveInvoiceResponse
{
    public string $invoiceIdentifier = '';
}
