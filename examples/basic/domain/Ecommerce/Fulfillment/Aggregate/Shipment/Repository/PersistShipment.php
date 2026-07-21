<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\ShipmentAddress;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\ShipmentItem;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\TrackingEvent;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Repository\Repository;
use PDOException;
use RuntimeException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment as ShipmentHandler;

/**
 * Persists Shipment with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistShipment extends EcommerceContext
{
    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param ShipmentHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws Exception
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(ShipmentHandler $handler): ContextResponseInterface
    {
        if ($handler->isMarkedForDeletion()) {
            $this->deleteAggregate($handler);
            return $this->result();
        }

        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();
        if ($ownsTransaction) {
            $writer->beginTransaction();
        }

        $isNew = false;
        try {
            $this->persistShipmentAddress($handler);
            $isNew = $this->persistShipment($handler);
            $this->persistShipmentItem($handler);
            $this->persistTrackingEvent($handler);

            $this->deleteRemovedEntities($handler);

            if ($ownsTransaction) {
                $writer->commit();
            }

            return $this->result();
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            }
            throw $e;
        }
    }

    /**
     * Persists shipmentAddress entity data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistShipmentAddress(ShipmentHandler $handler): void
    {
        $shipmentAddressData = $handler->getEntityData('shipmentAddress');
        if ($shipmentAddressData['isNew']) {
            if (!empty($shipmentAddressData['values'])) {
                $newId = $this->getRepository()->insert(
                    $shipmentAddressData['table'],
                    $shipmentAddressData['pkColumn'],
                    $this->normalizeBoolValues($shipmentAddressData['values'])
                );
                ($shipmentAddressData['onInserted'])($newId);
            }
        } elseif (!empty($shipmentAddressData['values'])) {
            $this->getRepository()->update(
                $shipmentAddressData['table'],
                $shipmentAddressData['pkColumn'],
                $shipmentAddressData['pkValue'],
                $this->normalizeBoolValues($shipmentAddressData['values'])
            );
        }
    }

    /**
     * Persists shipment entity data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistShipment(ShipmentHandler $handler): bool
    {
        $shipmentData = $handler->getEntityData('shipment');
        if ($shipmentData['isNew']) {
            if (!empty($shipmentData['values'])) {
                $newId = $this->getRepository()->insert(
                    $shipmentData['table'],
                    $shipmentData['pkColumn'],
                    $this->normalizeBoolValues($shipmentData['values'])
                );
                ($shipmentData['onInserted'])($newId);
            }
        } elseif (!empty($shipmentData['values'])) {
            $this->getRepository()->update(
                $shipmentData['table'],
                $shipmentData['pkColumn'],
                $shipmentData['pkValue'],
                $this->normalizeBoolValues($shipmentData['values'])
            );
        }

        return $shipmentData['isNew'];
    }

    /**
     * Persists shipmentItem collection data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistShipmentItem(ShipmentHandler $handler): void
    {
        foreach ($handler->getCollectionData('shipmentItem') as $shipmentItemData) {
            if ($shipmentItemData['isNew']) {
                if (!empty($shipmentItemData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $shipmentItemData['table'],
                        $shipmentItemData['pkColumn'],
                        $this->normalizeBoolValues($shipmentItemData['values'])
                    );
                    ($shipmentItemData['onInserted'])($newId);
                }
            } elseif (!empty($shipmentItemData['values'])) {
                $this->getRepository()->update(
                    $shipmentItemData['table'],
                    $shipmentItemData['pkColumn'],
                    $shipmentItemData['pkValue'],
                    $this->normalizeBoolValues($shipmentItemData['values'])
                );
            }
        }
    }

    /**
     * Persists trackingEvent collection data.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistTrackingEvent(ShipmentHandler $handler): void
    {
        foreach ($handler->getCollectionData('trackingEvent') as $trackingEventData) {
            if ($trackingEventData['isNew']) {
                if (!empty($trackingEventData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $trackingEventData['table'],
                        $trackingEventData['pkColumn'],
                        $this->normalizeBoolValues($trackingEventData['values'])
                    );
                    ($trackingEventData['onInserted'])($newId);
                }
            } elseif (!empty($trackingEventData['values'])) {
                $this->getRepository()->update(
                    $trackingEventData['table'],
                    $trackingEventData['pkColumn'],
                    $trackingEventData['pkValue'],
                    $this->normalizeBoolValues($trackingEventData['values'])
                );
            }
        }
    }

    /**
     * Deletes all tracked OWNED_ENTITY from the database.
     *
     * Delegates to Repository::deleteAll() for batch deletion.
     * Deletes are performed in FK-safe order.
     *
     * @param ShipmentHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteRemovedEntities(ShipmentHandler $handler): void
    {
        $removedIds = $handler->getRemovedIds();
        if (!empty($removedIds['shipmentItem'])) {
            $this->getRepository()->deleteAll(
                ShipmentItem::SOURCE,
                ShipmentItem::PRIMARY_KEY,
                $removedIds['shipmentItem']
            );
        }

        if (!empty($removedIds['trackingEvent'])) {
            $this->getRepository()->deleteAll(
                TrackingEvent::SOURCE,
                TrackingEvent::PRIMARY_KEY,
                $removedIds['trackingEvent']
            );
        }

        if (!empty($removedIds['shipmentAddress'])) {
            $this->getRepository()->deleteAll(
                ShipmentAddress::SOURCE,
                ShipmentAddress::PRIMARY_KEY,
                $removedIds['shipmentAddress']
            );
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param ShipmentHandler $handler The aggregate handler
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteAggregate(ShipmentHandler $handler): void
    {
        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('shipment');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        }

        try {
            $shipmentItemData = $handler->getCollectionData('shipmentItem');
            $shipmentItemIds = array_unique(array_filter(array_column($shipmentItemData, 'pkValue')));
            if (!empty($shipmentItemIds)) {
                $this->getRepository()->deleteAll(ShipmentItem::SOURCE, ShipmentItem::PRIMARY_KEY, $shipmentItemIds);
            }

            $trackingEventData = $handler->getCollectionData('trackingEvent');
            $trackingEventIds = array_unique(array_filter(array_column($trackingEventData, 'pkValue')));
            if (!empty($trackingEventIds)) {
                $this->getRepository()->deleteAll(TrackingEvent::SOURCE, TrackingEvent::PRIMARY_KEY, $trackingEventIds);
            }

            if ($aggregateId !== null) {
                $this->getRepository()->delete($rootData['table'], $rootData['pkColumn'], $aggregateId);
            }

            $shipmentAddressData = $handler->getEntityData('shipmentAddress');
            if ($shipmentAddressData['pkValue'] !== null) {
                $this->getRepository()->delete(ShipmentAddress::SOURCE, ShipmentAddress::PRIMARY_KEY, $shipmentAddressData['pkValue']);
            }

            if ($ownsTransaction) {
                $writer->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            }
            throw $e;
        }
    }

    /**
     * Gets Repository service (shared via Factory).
     *
     * @return Repository
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function getRepository(): Repository
    {
        return $this->handle(Repository::class, $this->resolveConnection());
    }

    /**
     * Casts PHP bool values to int (1/0) for database persistence.
     *
     * PDO binds PHP false as empty string by default, which MySQL rejects for
     * integer columns. This ensures bool values are stored as 0 or 1.
     *
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function normalizeBoolValues(array $values): array
    {
        return array_map(static fn($v) => is_bool($v) ? (int) $v : $v, $values);
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
            return $connection->getWriter()->pdo();
        }

        throw new \RuntimeException('No database connection configured');
    }
}
