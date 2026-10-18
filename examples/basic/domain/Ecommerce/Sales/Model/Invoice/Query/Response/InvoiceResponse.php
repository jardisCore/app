<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query\Response;

use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for InvoiceResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class InvoiceResponse implements AggregateResponse
{
    public string $identifier = '';
    public string $invoiceNumber = '';
    public string $orderNumber = '';
    public string $customerIdentifier = '';
    public string $status = '';
    public ?string $paymentMethod = null;

    /** @var numeric-string */
    public string $totalNet = '0';

    /** @var numeric-string */
    public string $taxRate = '0';

    /** @var numeric-string */
    public string $totalGross = '0';
    public string $currency = '';
    public ?string $note = null;
    public ?string $issuedAt = null;
    public ?string $dueAt = null;
    public ?string $paidAt = null;
    public ?string $createdAt = null;

    /** @var InvoiceLineResponse[] */
    public array $invoiceLine = [];
}
