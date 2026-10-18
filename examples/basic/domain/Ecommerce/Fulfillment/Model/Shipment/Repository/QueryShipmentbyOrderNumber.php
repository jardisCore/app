<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentByOrderNumberFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Fulfillment\Entity\Shipment as ShipmentEntity;

/**
 * List query for ShipmentByOrderNumber.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryShipmentByOrderNumber extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(ShipmentByOrderNumberFilter $filter): array
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

        $query = $query->and('shipment.order_number')->equals($filter->orderNumber);

        $query = $query
            ->orderBy('shipment.id', 'ASC')
            ->limit(min($filter->limit, 50), $filter->offset);

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
