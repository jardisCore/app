<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Query\Response;

use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for OrderResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class OrderResponse implements AggregateResponse
{
    public string $orderNumber = '';

    /** @var numeric-string */
    public string $totalAmount = '0';
    public string $status = '';
    public ?string $createdAt = null;
    public ?CustomerResponse $customer = null;

    /** @var OrderItemResponse[] */
    public array $orderItem = [];
}
