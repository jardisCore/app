<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\UpdateTrackingEvent as CommandUpdateTrackingEvent;

/**
 * Action: BuildUpdateTrackingEventData
 */
class BuildUpdateTrackingEventData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateTrackingEvent $updateTrackingEvent Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateTrackingEvent $updateTrackingEvent): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateTrackingEvent);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["tenant","shipmentIdentifier","trackingEventId"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->trackingEventsColumns());
    }
}
