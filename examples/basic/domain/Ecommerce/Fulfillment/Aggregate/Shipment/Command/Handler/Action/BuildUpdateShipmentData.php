<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\UpdateShipment as CommandUpdateShipment;

/**
 * Action: BuildUpdateShipmentData
 */
class BuildUpdateShipmentData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateShipment $updateShipment Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateShipment $updateShipment): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateShipment);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["shipmentIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->shipmentsColumns());
    }
}
