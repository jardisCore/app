<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query\Response;

use DateTime;
use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for ShipmentResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ShipmentResponse implements AggregateResponse
{
    public int $id = 0;
    public string $identifier = '';
    public string $orderNumber = '';
    public string $customerIdentifier = '';
    public string $carrier = '';
    public string $serviceLevel = '';
    public ?string $trackingNumber = null;
    public string $status = '';
    public ?int $weightGrams = null;
    public int $packageCount = 0;
    public ?float $insuranceValue = null;
    public ?DateTime $estimatedDelivery = null;
    public ?DateTime $shippedAt = null;
    public ?DateTime $deliveredAt = null;
    public ?string $note = null;
    public ?DateTime $createdAt = null;
    public ?ShipmentAddressResponse $shipmentAddress = null;

    /** @var ShipmentItemResponse[] */
    public array $shipmentItem = [];

    /** @var TrackingEventResponse[] */
    public array $trackingEvent = [];
}
