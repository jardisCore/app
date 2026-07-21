<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Repository;

use Ecommerce\EcommerceContext;

/**
 * Transforms flat query results into nested Shipment structure.
 *
 * Input: Arrays grouped by entity name (from query results)
 * Output: Nested array structure matching aggregate definition
 */
class TransformShipment extends EcommerceContext
{
    /**
     * Transform flat query results into nested aggregate structure.
     *
     * @param array<string, array<int, mixed>> $container Query results grouped by entity
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(array $container = []): array
    {
        $result = [];
        $dto = [];
        $processedIds = [];

        foreach ($container['shipment'] as $index => $shipment) {
            if (is_numeric($index) && !isset($processedIds[$shipment['id']])) {
                $processedIds[$shipment['id']] = true;
                $parent = $dto['shipment'] = $shipment;

                $this->transformShipmentShipmentAddress($parent, $dto, $container);
                $this->transformShipmentShipmentItem($parent, $dto, $container);
                $this->transformShipmentTrackingEvent($parent, $dto, $container);

                $result[] = $parent;
            }
        }

        return $result;
    }

    /**
     * Transform shipmentAddress entities (ERM: one).
     * Relates: id = shipment.delivery_address_id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformShipmentShipmentAddress(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['shipmentAddress'] as $index => $shipmentAddress) {
            if (is_numeric($index) && $shipmentAddress['id'] == $dto['shipment']['delivery_address_id']) {
                $parent['shipmentAddress'] = $shipmentAddress;

                break; // ERM: one - stop after first match
            }
        }
    }
    /**
     * Transform shipmentItem entities (ERM: many).
     * Relates: shipment_id = shipment.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformShipmentShipmentItem(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['shipmentItem'] as $index => $shipmentItem) {
            if (is_numeric($index) && $shipmentItem['shipment_id'] == $dto['shipment']['id']) {
                $parent['shipmentItem'][] = $shipmentItem;
            }
        }
    }
    /**
     * Transform trackingEvent entities (ERM: many).
     * Relates: shipment_id = shipment.id
     *
     * @param array<string, mixed> &$parent Parent entity array (modified by reference)
     * @param array<string, mixed> $dto Data transfer object with context
     * @param array<string, array<int, mixed>> &$container Query results (by reference)
     * @return void
     */
    protected function transformShipmentTrackingEvent(array &$parent, array $dto, array &$container): void
    {
        foreach ($container['trackingEvent'] as $index => $trackingEvent) {
            if (is_numeric($index) && $trackingEvent['shipment_id'] == $dto['shipment']['id']) {
                $parent['trackingEvent'][] = $trackingEvent;
            }
        }
    }
}
