<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Response;

use DateTime;
use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for OrderResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class OrderResponse implements AggregateResponse
{
    public int $id = 0;
    public string $orderNumber = '';
    public float $totalAmount = 0.0;
    public string $status = '';
    public ?DateTime $createdAt = null;
    public ?CustomerResponse $customer = null;

    /** @var OrderItemResponse[] */
    public array $orderItem = [];
}
