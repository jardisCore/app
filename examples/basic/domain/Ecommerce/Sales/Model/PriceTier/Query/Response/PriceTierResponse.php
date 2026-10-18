<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Query\Response;

use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for PriceTierResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class PriceTierResponse implements AggregateResponse
{
    public string $identifier = '';
    public string $tierCode = '';
    public int $minQuantity = 0;

    /** @var numeric-string */
    public string $unitPrice = '0';
    public ?string $createdAt = null;
}
