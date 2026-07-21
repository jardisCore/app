<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentTrackingListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Fulfillment\Entity\TrackingEvent as TrackingEventEntity;

/**
 * List query for ShipmentTrackingList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryShipmentTrackingList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(ShipmentTrackingListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(TrackingEventEntity::SOURCE, 'trackingEvent')
            ->select('trackingEvent.id AS id, trackingEvent.event_code AS eventCode, trackingEvent.status AS status, trackingEvent.location AS location, trackingEvent.postal_code AS postalCode, trackingEvent.detail AS detail, trackingEvent.occurred_at AS occurredAt, trackingEvent.reported_at AS reportedAt, shipment.identifier AS identifier, COUNT(*) OVER() AS total_count')
            ->leftJoin('shipments', 'shipment.id = trackingEvent.shipment_id', 'shipment');

        $query = $query->where('shipment.identifier')->equals($filter->shipmentIdentifier);

        $query = $query
            ->orderBy('trackingEvent.occurred_at', 'ASC')
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
