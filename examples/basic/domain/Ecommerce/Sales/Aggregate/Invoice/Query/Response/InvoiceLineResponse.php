<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query\Response;

use DateTime;

/**
 * Read projection for InvoiceLineResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class InvoiceLineResponse
{
    public string $identifier = '';
    public int $position = 0;
    public string $description = '';
    public float $quantity = 0.0;
    public string $unit = '';
    public float $unitPrice = 0.0;
    public ?float $discountPercent = null;
    public float $lineTotal = 0.0;
    public int $taxIncluded = 0;
    public ?DateTime $createdAt = null;
}
