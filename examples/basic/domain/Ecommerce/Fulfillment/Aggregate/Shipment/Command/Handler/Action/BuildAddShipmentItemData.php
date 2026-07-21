<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\AddShipmentItem as CommandAddShipmentItem;

/**
 * Action: BuildAddShipmentItemData
 */
class BuildAddShipmentItemData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandAddShipmentItem $addShipmentItem Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandAddShipmentItem $addShipmentItem): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($addShipmentItem);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["shipmentIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->shipmentItemsColumns());
    }
}
