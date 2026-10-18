<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Query\Response;

/**
 * Read projection for OrderItemResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class OrderItemResponse
{
    public string $identifier = '';
    public string $productIdentifier = '';
    public int $quantity = 0;

    /** @var numeric-string */
    public string $unitPrice = '0';

    /** @var numeric-string */
    public string $subtotal = '0';

    /** @var ItemDiscountResponse[] */
    public array $itemDiscount = [];
}
