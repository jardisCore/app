<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\Response\ShipmentResponse;
use Ecommerce\Fulfillment\Aggregate\Shipment\Repository\ShipmentRepository;
use Ecommerce\Response\DomainResponseTransformer;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Data\FieldMapper;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentByIds as QueryShipmentByIds;

/**
 * Query endpoint: GetShipmentByIds
 */
class GetShipmentByIdsHandler extends EcommerceContext
{
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

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->handle(ShipmentRepository::class)->getShipmentByIds($shipmentByIds, false);

        /** @var array<string, array<string, string>> $readMaps */
        $readMaps = [
            'shipment' => [
                'id' => 'id',
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

        /** @var array<int, ShipmentResponse> $projected */
        $projected = array_map(
            function (array $row) use ($mapEntity): ShipmentResponse {
                $mapped = $this->handle(FieldMapper::class)->fromAggregate($row, $mapEntity, 'shipment');
                $shipmentResponse = new ShipmentResponse();
                $this->handle(Hydration::class)->hydrateAggregate($shipmentResponse, $mapped);
                return $shipmentResponse;
            },
            $result,
        );

        $this->result()->setData(['shipment' => $projected]);

        return $this->handle(DomainResponseTransformer::class)->transform($this->result());
    }
}
