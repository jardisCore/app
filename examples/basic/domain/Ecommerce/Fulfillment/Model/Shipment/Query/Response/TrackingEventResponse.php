<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query\Response;

/**
 * Read projection for TrackingEventResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class TrackingEventResponse
{
    public int $id = 0;
    public string $eventCode = '';
    public string $status = '';
    public ?string $location = null;
    public ?string $postalCode = null;
    public ?string $detail = null;
    public ?string $occurredAt = null;
    public ?string $reportedAt = null;
}
