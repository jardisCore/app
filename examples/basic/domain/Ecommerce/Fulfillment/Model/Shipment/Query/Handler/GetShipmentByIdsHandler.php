<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Query\Response\ShipmentResponse;
use Ecommerce\Fulfillment\Model\Shipment\Repository\ShipmentRepository;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByIds as QueryShipmentByIds;

/**
 * Query endpoint: GetShipmentByIds
 */
class GetShipmentByIdsHandler extends EcommerceContext
{
    private const BULK_WARN_ABOVE = 100;

    /**
     * Executes the Query endpoint operation.
     *
     * @return DomainResponseInterface
     * @throws Throwable
     * @throws Exception
     * @throws ReflectionException
     */
    public function __invoke(): DomainResponseInterface
    {
        /** @var QueryShipmentByIds $shipmentByIds */
        $shipmentByIds = $this->payload();

        $logger = $this->resource()->logger();
        if ($logger !== null) {
            $count = count($shipmentByIds->ids);
            if ($count > self::BULK_WARN_ABOVE) {
                $logger->warning('Bulk read {handler} received {count} keys (threshold {threshold}).', [
                    'handler' => self::class,
                    'count' => $count,
                    'threshold' => self::BULK_WARN_ABOVE,
                ]);
            }
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(ShipmentRepository::class)->getShipmentByIds($shipmentByIds, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'shipment' => [
                'identifier' => 'identifier',
                'orderNumber' => 'order_number',
                'customerIdentifier' => 'customer_identifier',
                'carrier' => 'carrier',
                'serviceLevel' => 'service_level',
                'trackingNumber' => 'tracking_number',
                'status' => 'status',
                'weightGrams' => 'weight_grams',
                'packageCount' => 'package_count',
                'insuranceValue' => 'insurance_value',
                'estimatedDelivery' => 'estimated_delivery',
                'shippedAt' => 'shipped_at',
                'deliveredAt' => 'delivered_at',
                'note' => 'note',
                'createdAt' => 'created_at',
            ],
            'shipmentAddress' => [
                'recipientName' => 'recipient_name',
                'company' => 'company',
                'street' => 'street',
                'street2' => 'street2',
                'city' => 'city',
                'postalCode' => 'postal_code',
                'state' => 'state',
                'country' => 'country',
                'phone' => 'phone',
            ],
            'shipmentItem' => [
                'identifier' => 'identifier',
                'productSku' => 'product_sku',
                'productName' => 'product_name',
                'quantity' => 'quantity',
                'weightGrams' => 'weight_grams',
                'isFragile' => 'is_fragile',
                'serialNumber' => 'serial_number',
                'lotNumber' => 'lot_number',
            ],
            'trackingEvent' => [
                'id' => 'id',
                'eventCode' => 'event_code',
                'status' => 'status',
                'location' => 'location',
                'postalCode' => 'postal_code',
                'detail' => 'detail',
                'occurredAt' => 'occurred_at',
                'reportedAt' => 'reported_at',
            ],
        ];
        $mapEntity = static fn(string $entity): array => $readMaps[$entity] ?? [];

        $wireTypes = [
            'estimatedDelivery' => 'date',
            'shippedAt' => 'date-time',
            'deliveredAt' => 'date-time',
            'createdAt' => 'date-time',
            'trackingEvent' => [
                'occurredAt' => 'date-time',
                'reportedAt' => 'date-time',
            ],
        ];

        $projectWire = $this->handle(ProjectWire::class);
        $fieldMapper = $this->handle(FieldMapper::class);
        $hydration = $this->handle(Hydration::class);

        /** @var array<int, ShipmentResponse> $projected */
        $projected = array_map(
            function (array $row) use (
                $mapEntity,
                $fieldMapper,
                $hydration,
                $projectWire,
                $wireTypes
            ): ShipmentResponse {
                $mapped = $fieldMapper->fromAggregate($row, $mapEntity, 'shipment');
                $mapped = $projectWire($mapped, $wireTypes);
                $shipmentResponse = new ShipmentResponse();
                $hydration->hydrateAggregate($shipmentResponse, $mapped);
                return $shipmentResponse;
            },
            $result,
        );

        $this->result()->setData(['@type' => 'shipmentList', 'items' => $projected]);

        return ($this->transformer())($this->result());
    }
}
