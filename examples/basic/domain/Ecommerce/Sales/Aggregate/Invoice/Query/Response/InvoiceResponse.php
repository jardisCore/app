<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query\Response;

use DateTime;
use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for InvoiceResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class InvoiceResponse implements AggregateResponse
{
    public int $id = 0;
    public string $identifier = '';
    public string $invoiceNumber = '';
    public string $orderNumber = '';
    public string $customerIdentifier = '';
    public string $status = '';
    public ?string $paymentMethod = null;
    public float $totalNet = 0.0;
    public float $taxRate = 0.0;
    public float $totalGross = 0.0;
    public string $currency = '';
    public ?string $note = null;
    public ?DateTime $issuedAt = null;
    public ?DateTime $dueAt = null;
    public ?DateTime $paidAt = null;
    public ?DateTime $createdAt = null;

    /** @var InvoiceLineResponse[] */
    public array $invoiceLine = [];
}
