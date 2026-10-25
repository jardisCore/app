<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\UpdateShipmentItem as CommandUpdateShipmentItem;

/**
 * Action: BuildUpdateShipmentItemData
 */
class BuildUpdateShipmentItemData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateShipmentItem $updateShipmentItem Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateShipmentItem $updateShipmentItem): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateShipmentItem);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["tenant","shipmentIdentifier","shipmentItemIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->shipmentItemsColumns());
    }
}
