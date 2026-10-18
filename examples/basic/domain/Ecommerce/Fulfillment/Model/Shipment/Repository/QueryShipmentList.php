<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Fulfillment\Entity\Shipment as ShipmentEntity;

/**
 * List query for ShipmentList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryShipmentList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(ShipmentListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(ShipmentEntity::SOURCE, 'shipment')
            ->select(
                'shipment.identifier AS identifier, shipment.order_number AS orderNumber, '
                . 'shipment.customer_identifier AS customerIdentifier, shipment.carrier AS carrier, '
                . 'shipment.service_level AS serviceLevel, shipment.tracking_number AS trackingNumber, '
                . 'shipment.status AS status, shipment.weight_grams AS weightGrams, '
                . 'shipment.package_count AS packageCount, shipment.insurance_value AS insuranceValue, '
                . 'shipment.estimated_delivery AS estimatedDelivery, shipment.shipped_at AS shippedAt, '
                . 'shipment.delivered_at AS deliveredAt, shipment.note AS note, shipment.created_at AS createdAt, '
                . 'COUNT(*) OVER() AS total_count'
            );

        $hasId = $filter->id !== null;
        $hasIdentifier = $filter->identifier !== null;
        $hasOrderNumber = $filter->orderNumber !== null;
        $hasCustomerIdentifier = $filter->customerIdentifier !== null;
        $hasDeliveryAddressId = $filter->deliveryAddressId !== null;
        $hasCarrier = $filter->carrier !== null;
        $hasServiceLevel = $filter->serviceLevel !== null;
        $hasTrackingNumber = $filter->trackingNumber !== null;
        $hasStatus = $filter->status !== null;
        $hasWeightGrams = $filter->weightGrams !== null;
        $hasPackageCount = $filter->packageCount !== null;
        $hasInsuranceValue = $filter->insuranceValue !== null;
        $hasEstimatedDeliveryFrom = $filter->estimatedDeliveryFrom !== null;
        $hasEstimatedDeliveryUntil = $filter->estimatedDeliveryUntil !== null;
        $hasShippedAtFrom = $filter->shippedAtFrom !== null;
        $hasShippedAtUntil = $filter->shippedAtUntil !== null;
        $hasDeliveredAtFrom = $filter->deliveredAtFrom !== null;
        $hasDeliveredAtUntil = $filter->deliveredAtUntil !== null;
        $hasNote = $filter->note !== null;
        $hasCreatedAtFrom = $filter->createdAtFrom !== null;
        $hasCreatedAtUntil = $filter->createdAtUntil !== null;

        if ($hasId) {
            $query = $query->and('shipment.id')->equals($filter->id);
        }
        if ($hasIdentifier) {
            $query = $query->and('shipment.identifier')->equals($filter->identifier);
        }
        if ($hasOrderNumber) {
            $query = $query->and('shipment.order_number')->equals($filter->orderNumber);
        }
        if ($hasCustomerIdentifier) {
            $query = $query->and('shipment.customer_identifier')->equals($filter->customerIdentifier);
        }
        if ($hasDeliveryAddressId) {
            $query = $query->and('shipment.delivery_address_id')->equals($filter->deliveryAddressId);
        }
        if ($hasCarrier) {
            $query = $query->and('shipment.carrier')->equals($filter->carrier);
        }
        if ($hasServiceLevel) {
            $query = $query->and('shipment.service_level')->equals($filter->serviceLevel);
        }
        if ($hasTrackingNumber) {
            $query = $query->and('shipment.tracking_number')->equals($filter->trackingNumber);
        }
        if ($hasStatus) {
            $query = $query->and('shipment.status')->equals($filter->status);
        }
        if ($hasWeightGrams) {
            $query = $query->and('shipment.weight_grams')->equals($filter->weightGrams);
        }
        if ($hasPackageCount) {
            $query = $query->and('shipment.package_count')->equals($filter->packageCount);
        }
        if ($hasInsuranceValue) {
            $query = $query->and('shipment.insurance_value')->equals($filter->insuranceValue);
        }
        if ($hasEstimatedDeliveryFrom) {
            $query = $query->and('shipment.estimated_delivery')->greaterEquals($filter->estimatedDeliveryFrom);
        }
        if ($hasEstimatedDeliveryUntil) {
            $query = $query->and('shipment.estimated_delivery')->lowerEquals($filter->estimatedDeliveryUntil);
        }
        if ($hasShippedAtFrom) {
            $query = $query->and('shipment.shipped_at')->greaterEquals($filter->shippedAtFrom);
        }
        if ($hasShippedAtUntil) {
            $query = $query->and('shipment.shipped_at')->lowerEquals($filter->shippedAtUntil);
        }
        if ($hasDeliveredAtFrom) {
            $query = $query->and('shipment.delivered_at')->greaterEquals($filter->deliveredAtFrom);
        }
        if ($hasDeliveredAtUntil) {
            $query = $query->and('shipment.delivered_at')->lowerEquals($filter->deliveredAtUntil);
        }
        if ($hasNote) {
            $query = $query->and('shipment.note')->equals($filter->note);
        }
        if ($hasCreatedAtFrom) {
            $query = $query->and('shipment.created_at')->greaterEquals($filter->createdAtFrom);
        }
        if ($hasCreatedAtUntil) {
            $query = $query->and('shipment.created_at')->lowerEquals($filter->createdAtUntil);
        }

        $query = $query
            ->orderBy('shipment.estimated_delivery', 'DESC')
            ->orderBy('shipment.id', 'ASC')
            ->limit(min($filter->limit, 20), $filter->offset);

        return $this->handle(Repository::class, $this->resolveConnection())->findByQuery($query);
    }

    /**
     * Resolves the database connection to a PDO instance.
     *
     * Handles ConnectionPoolInterface (read/write splitting) and plain PDO.
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        $connection = $this->resource()->dbConnection();

        if ($connection instanceof \PDO) {
            return $connection;
        }

        if ($connection instanceof ConnectionPoolInterface) {
            return $connection->getReader()->pdo();
        }

        throw new \RuntimeException('No database connection configured');
    }
}
