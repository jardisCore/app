<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Aggregate;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\ShipmentAddress;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\ShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\TrackingEvent;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Shipment aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setShipment() - Set/replace root entity
 * - overlayReadBasis() - Lay the caller's read basis over the root snapshot (CAS)
 *
 * OWNED_ENTITY methods:
 * - setShipmentAddress() - Set/replace shipmentAddress
 * - addShipmentItem() - Add to shipmentItem collection
 * - removeShipmentItem() - Remove from shipmentItem collection (tracks for DELETE)
 * - updateShipmentItem() - Update in shipmentItem collection (snapshot stays, change tracking)
 * - addTrackingEvent() - Add to trackingEvent collection
 * - removeTrackingEvent() - Remove from trackingEvent collection (tracks for DELETE)
 * - updateTrackingEvent() - Update in trackingEvent collection (snapshot stays, change tracking)
 *
 * Persistence API:
 * - getEntityData() - Raw data for persistence
 * - getCollectionData() - Collection data for persistence
 * - getRemovedIds() - IDs marked for deletion
 * - isMarkedForDeletion() - Check if aggregate is marked for deletion
 */
class Shipment extends EcommerceContext
{
    private ?ShipmentAggregate $aggregate = null;

    /** @var bool Flag to mark entire aggregate for deletion */
    private bool $markedForDeletion = false;

    /** @var array<string, array<int>> Tracks removed entity IDs by entity name */
    private array $removedIds = [
        'shipmentAddress' => [],
        'shipmentItem' => [],
        'trackingEvent' => [],
    ];

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload,
        string $version,
        ?ShipmentAggregate $aggregate = null,
        int $depth = 0,
        bool $entry = true
    ) {
        parent::__construct($domainKernel, $payload, $version, $depth, $entry);
        $this->aggregate = $aggregate;
    }

    /**
     * Sets/replaces the root Shipment entity.
     * Pass null to mark entire aggregate for deletion.
     *
     * @param array<string, mixed>|null $data Root entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setShipment(?array $data): void
    {
        if ($data === null) {
            $this->remove();
            return;
        }

        $this->getHydration()->apply($this->aggregate, $data);
    }

    /**
     * Lays the read basis of the caller over the root snapshot.
     *
     * The next setShipment() is diffed against these values, so the
     * persist's expected-values check guards what the caller read, not what
     * was loaded just now. Call it BEFORE setShipment().
     *
     * @param array<string, mixed> $columns Column => value the caller read
     * @throws ReflectionException
     * @throws Throwable
     */
    public function overlayReadBasis(array $columns): void
    {
        $this->getHydration()->hydrate($this->aggregate, $columns);
    }

    /**
     * Marks the entire aggregate for deletion.
     *
     * When persisted, the entire aggregate (including all OWNED_ENTITY)
     * will be deleted from the database.
     */
    public function remove(): void
    {
        $this->markedForDeletion = true;
    }

    /**
     * Returns a detached copy of the aggregate for reading.
     *
     * The returned object is a deep clone - modifications do NOT
     * affect the original aggregate. Use this for read-only access
     * to aggregate data (e.g., validation, comparison).
     *
     * @return ShipmentAggregate Cloned aggregate data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getData(): ShipmentAggregate
    {
        /** @var ShipmentAggregate */
        return $this->getHydration()->cloneAggregate($this->aggregate);
    }

    /**
     * Returns whether the aggregate is marked for deletion.
     */
    public function isMarkedForDeletion(): bool
    {
        return $this->markedForDeletion;
    }

    /**
     * Sets/replaces the shipmentAddress entity.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function setShipmentAddress(array $data): void
    {
        $this->handle(namespace\Action\SetShipmentAddress::class)($this->aggregate, $data);
    }

    /**
     * Adds a ShipmentItem to the shipmentItem collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addShipmentItem(array $data): void
    {
        $this->handle(namespace\Action\AddShipmentItem::class)($this->aggregate, $data);
    }

    /**
     * Removes a ShipmentItem from the shipmentItem collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeShipmentItem(int $id): void
    {
        $removals = $this->handle(namespace\Action\RemoveShipmentItem::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Updates a ShipmentItem in the shipmentItem collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function updateShipmentItem(int $id, array $data): void
    {
        $this->handle(namespace\Action\UpdateShipmentItem::class)($this->aggregate, $id, $data);
    }

    /**
     * Adds a TrackingEvent to the trackingEvent collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addTrackingEvent(array $data): void
    {
        $this->handle(namespace\Action\AddTrackingEvent::class)($this->aggregate, $data);
    }

    /**
     * Removes a TrackingEvent from the trackingEvent collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeTrackingEvent(int $id): void
    {
        $removals = $this->handle(namespace\Action\RemoveTrackingEvent::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Updates a TrackingEvent in the trackingEvent collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function updateTrackingEvent(int $id, array $data): void
    {
        $this->handle(namespace\Action\UpdateTrackingEvent::class)($this->aggregate, $id, $data);
    }

    /**
     * Returns entity data as array for persistence.
     *
     * For new entities returns all values (toArray).
     * For existing entities returns only changed values.
     *
     * @param string $entityName Entity property name
     * @return array{table: string, pkColumn: string, isNew: bool, values: array<string, mixed>,
     *     expected: array<string, mixed>, snapshot: array<string, mixed>, pkValue: int|string|null,
     *     onInserted: \Closure}
     * @throws InvalidArgumentException If entity name is unknown
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getEntityData(string $entityName): array
    {
        $hydration = $this->getHydration();

        [$entity, $table, $pkColumn, $generatedColumns] = match ($entityName) {
            'shipment' => [
                $this->aggregate,
                ShipmentAggregate::SOURCE,
                ShipmentAggregate::PRIMARY_KEY,
                ['id', 'package_count', 'created_at']
            ],
            'shipmentAddress' => [
                $this->aggregate->getShipmentAddress(),
                ShipmentAddress::SOURCE,
                ShipmentAddress::PRIMARY_KEY,
                ['id']
            ],
            default => throw new InvalidArgumentException("Unknown entity: $entityName"),
        };

        $pkGetter = 'get' . ucfirst($pkColumn);
        $isNew = $entity === null || $entity->$pkGetter() === null;

        if ($isNew && $entity !== null) {
            if ($entityName === 'shipment') {
                $hydration->hydrate($entity, [
                    'identifier' => $this->aggregate->getIdentifier()
                        ?? $this->handle(Identity::class)->generateUuid7(),
                ]);
            }
        }

        $onInserted = function (int|string $id) use ($hydration, $entity, $entityName, $pkColumn): void {
            if ($entity !== null) {
                $hydration->hydrate($entity, [$pkColumn => $id]);
            }
            if ($entityName === 'shipment') {
                foreach ($this->aggregate->getShipmentItem() as $child) {
                    $hydration->hydrate($child, ['shipment_id' => $id]);
                }
                foreach ($this->aggregate->getTrackingEvent() as $child) {
                    $hydration->hydrate($child, ['shipment_id' => $id]);
                }
            } elseif ($entityName === 'shipmentAddress') {
                $dependent = $this->aggregate;
                if ($dependent !== null) {
                    $hydration->hydrate($dependent, ['delivery_address_id' => $id]);
                }
            }
        };

        return [
            'table' => $table,
            'pkColumn' => $pkColumn,
            'isNew' => $isNew,
            'values' => $entity !== null ? ($isNew ? array_filter(
                $hydration->toArray($entity),
                fn($value, $column) => $value !== null || !in_array($column, $generatedColumns, true),
                ARRAY_FILTER_USE_BOTH
            ) : $hydration->getChanges($entity)) : [],
            'expected' => $entity !== null
                ? ($isNew ? [] : array_intersect_key($hydration->getSnapshot($entity), $hydration->getChanges($entity)))
                : [],
            'snapshot' => $entityName === 'shipment' && $entity !== null && !$isNew
                ? $hydration->getSnapshot($entity)
                : [],
            'pkValue' => $entity !== null ? $entity->$pkGetter() : null,
            'onInserted' => $onInserted,
        ];
    }

    /**
     * Returns collection data as array of entity data arrays.
     *
     * @param string $collectionName Collection property name
     * @return array<int, array{table: string, pkColumn: string, isNew: bool, values: array<string, mixed>,
     *     expected: array<string, mixed>, pkValue: int|string|null, onInserted: \Closure}>
     * @throws InvalidArgumentException If collection name is unknown
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getCollectionData(string $collectionName): array
    {
        $hydration = $this->getHydration();

        return match ($collectionName) {
            'shipmentItem' => array_map(function (object $entity) use ($hydration) {
                $pkGetter = 'get' . ucfirst(ShipmentItem::PRIMARY_KEY);
                $isNew = $entity->$pkGetter() === null;
                if ($isNew) {
                    $hydration->hydrate($entity, [
                        'identifier' => $entity->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
                        'shipment_id' => $this->aggregate->getId(),
                    ]);
                }
                return [
                    'table' => ShipmentItem::SOURCE,
                    'pkColumn' => ShipmentItem::PRIMARY_KEY,
                    'isNew' => $isNew,
                    'values' => $isNew ? array_filter(
                        $hydration->toArray($entity),
                        fn($value, $column) => $value !== null || !in_array($column, ['id'], true),
                        ARRAY_FILTER_USE_BOTH
                    ) : $hydration->getChanges($entity),
                    'expected' => $isNew
                        ? []
                        : array_intersect_key($hydration->getSnapshot($entity), $hydration->getChanges($entity)),
                    'pkValue' => $entity->$pkGetter(),
                    'onInserted' => function (int|string $id) use ($hydration, $entity): void {
                        $hydration->hydrate($entity, [ShipmentItem::PRIMARY_KEY => $id]);
                    },
                ];
            }, $this->aggregate->getShipmentItem()),
            'trackingEvent' => array_map(function (object $entity) use ($hydration) {
                $pkGetter = 'get' . ucfirst(TrackingEvent::PRIMARY_KEY);
                $isNew = $entity->$pkGetter() === null;
                if ($isNew) {
                    $hydration->hydrate($entity, [
                        'shipment_id' => $this->aggregate->getId(),
                    ]);
                }
                return [
                    'table' => TrackingEvent::SOURCE,
                    'pkColumn' => TrackingEvent::PRIMARY_KEY,
                    'isNew' => $isNew,
                    'values' => $isNew ? array_filter(
                        $hydration->toArray($entity),
                        fn($value, $column) => $value !== null || !in_array($column, ['id'], true),
                        ARRAY_FILTER_USE_BOTH
                    ) : $hydration->getChanges($entity),
                    'expected' => $isNew
                        ? []
                        : array_intersect_key($hydration->getSnapshot($entity), $hydration->getChanges($entity)),
                    'pkValue' => $entity->$pkGetter(),
                    'onInserted' => function (int|string $id) use ($hydration, $entity): void {
                        $hydration->hydrate($entity, [TrackingEvent::PRIMARY_KEY => $id]);
                    },
                ];
            }, $this->aggregate->getTrackingEvent()),
            default => throw new InvalidArgumentException("Unknown collection: $collectionName"),
        };
    }

    /**
     * Gets all removed entity IDs organized by entity name.
     *
     * @return array<string, array<int>>
     */
    public function getRemovedIds(): array
    {
        return $this->removedIds;
    }

    /**
     * Helper method to track entity removal.
     *
     * @param string $entityName Entity property name
     * @param object|null $entity Entity to track
     */
    private function trackDeletion(string $entityName, ?object $entity): void
    {
        if ($entity !== null) {
            $primaryKey = $entity::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $id = $entity->$getter();

            if ($id !== null) {
                $this->removedIds[$entityName][] = $id;
            }
        }
    }

    /**
     * Gets Hydration service (shared via Factory).
     * @throws Throwable
     */
    private function getHydration(): Hydration
    {
        return $this->handle(Hydration::class);
    }
}
