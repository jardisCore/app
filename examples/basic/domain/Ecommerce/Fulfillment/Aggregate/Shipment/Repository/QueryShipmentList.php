<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
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
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(ShipmentListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(ShipmentEntity::SOURCE, 'shipment')
            ->select('shipment.id AS id, shipment.identifier AS identifier, shipment.order_number AS orderNumber, shipment.customer_identifier AS customerIdentifier, shipment.carrier AS carrier, shipment.service_level AS serviceLevel, shipment.tracking_number AS trackingNumber, shipment.status AS status, shipment.package_count AS packageCount, shipment.estimated_delivery AS estimatedDelivery, shipment.shipped_at AS shippedAt, shipment.delivered_at AS deliveredAt, COUNT(*) OVER() AS total_count');

        if ($filter->status !== null) {
            $query = $query->where('shipment.status')->equals($filter->status);
        }
        if ($filter->carrier !== null) {
            $query = $query->and('shipment.carrier')->equals($filter->carrier);
        }
        if ($filter->orderNumber !== null) {
            $query = $query->and('shipment.order_number')->equals($filter->orderNumber);
        }

        $query = $query
            ->orderBy('shipment.created_at', 'DESC')
            ->limit($filter->limit, $filter->offset);

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
