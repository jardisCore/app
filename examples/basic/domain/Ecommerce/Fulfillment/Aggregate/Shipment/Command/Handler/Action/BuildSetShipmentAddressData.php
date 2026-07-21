<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\SetShipmentAddress as CommandSetShipmentAddress;

/**
 * Action: BuildSetShipmentAddressData
 */
class BuildSetShipmentAddressData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandSetShipmentAddress $setShipmentAddress Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandSetShipmentAddress $setShipmentAddress): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($setShipmentAddress);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["shipmentIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->shipmentAddressesColumns());
    }
}
