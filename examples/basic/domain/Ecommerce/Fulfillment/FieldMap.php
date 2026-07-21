<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment;

/**
 * Central naming map: DTO property names <-> DB column names, per table.
 *
 * Pure naming container (Schema + FieldMap.yaml overrides + default
 * ToPropertyName). One method per BC table:
 *   {table}Columns() — full DTO→column map for FieldMapper::toColumns (write path)
 *
 * The read path (G4 strip + root-id normalization) is aggregate-structural
 * and lives at the aggregate read edge (the query handler), not here.
 *
 * Loaded via $this->handle(FieldMap::class) for ClassVersion support.
 */
class FieldMap
{
    /** @return array<string, string> field -> column mapping */
    public function shipmentAddressesColumns(): array
    {
        return [
            'id' => 'id',
            'recipientName' => 'recipient_name',
            'company' => 'company',
            'street' => 'street',
            'street2' => 'street2',
            'city' => 'city',
            'postalCode' => 'postal_code',
            'state' => 'state',
            'country' => 'country',
            'phone' => 'phone',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function shipmentItemsColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'shipmentId' => 'shipment_id',
            'productSku' => 'product_sku',
            'productName' => 'product_name',
            'quantity' => 'quantity',
            'weightGrams' => 'weight_grams',
            'isFragile' => 'is_fragile',
            'serialNumber' => 'serial_number',
            'lotNumber' => 'lot_number',
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function shipmentsColumns(): array
    {
        return [
            'id' => 'id',
            'identifier' => 'identifier',
            'orderNumber' => 'order_number',
            'customerIdentifier' => 'customer_identifier',
            'deliveryAddressId' => 'delivery_address_id',
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
        ];
    }

    /** @return array<string, string> field -> column mapping */
    public function trackingEventsColumns(): array
    {
        return [
            'id' => 'id',
            'shipmentId' => 'shipment_id',
            'eventCode' => 'event_code',
            'status' => 'status',
            'location' => 'location',
            'postalCode' => 'postal_code',
            'detail' => 'detail',
            'occurredAt' => 'occurred_at',
            'reportedAt' => 'reported_at',
        ];
    }
}
