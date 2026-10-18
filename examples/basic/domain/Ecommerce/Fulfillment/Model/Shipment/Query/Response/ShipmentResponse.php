<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query\Response;

use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for ShipmentResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ShipmentResponse implements AggregateResponse
{
    public string $identifier = '';
    public string $orderNumber = '';
    public string $customerIdentifier = '';
    public string $carrier = '';
    public string $serviceLevel = '';
    public ?string $trackingNumber = null;
    public string $status = '';
    public ?int $weightGrams = null;
    public int $packageCount = 0;

    /** @var ?numeric-string */
    public ?string $insuranceValue = null;
    public ?string $estimatedDelivery = null;
    public ?string $shippedAt = null;
    public ?string $deliveredAt = null;
    public ?string $note = null;
    public ?string $createdAt = null;
    public ?ShipmentAddressResponse $shipmentAddress = null;

    /** @var ShipmentItemResponse[] */
    public array $shipmentItem = [];

    /** @var TrackingEventResponse[] */
    public array $trackingEvent = [];
}
