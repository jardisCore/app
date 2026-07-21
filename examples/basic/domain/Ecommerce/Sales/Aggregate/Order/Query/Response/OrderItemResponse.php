<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Response;

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
    public float $unitPrice = 0.0;
    public float $subtotal = 0.0;

    /** @var ItemDiscountResponse[] */
    public array $itemDiscount = [];
}
