<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query\Response;

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

    /** @var numeric-string */
    public string $quantity = '0';
    public string $unit = '';

    /** @var numeric-string */
    public string $unitPrice = '0';

    /** @var ?numeric-string */
    public ?string $discountPercent = null;

    /** @var numeric-string */
    public string $lineTotal = '0';
    public int $taxIncluded = 0;
    public ?string $createdAt = null;
}
