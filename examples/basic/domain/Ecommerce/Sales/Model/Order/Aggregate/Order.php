<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Aggregate;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Address;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Customer;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\ItemDiscount;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order as OrderAggregate;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\OrderItem;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Order aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setOrder() - Set/replace root entity
 * - overlayReadBasis() - Lay the caller's read basis over the root snapshot (CAS)
 *
 * OWNED_ENTITY methods:
 * - setCustomer() - Set/replace customer
 * - addOrderItem() - Add to orderItem collection
 * - removeOrderItem() - Remove from orderItem collection (tracks for DELETE)
 * - updateOrderItem() - Update in orderItem collection (snapshot stays, change tracking)
 * - addItemDiscount() - Add to itemDiscount via orderItem
 * - removeItemDiscount() - Remove from itemDiscount via orderItem
 * - updateItemDiscount() - Update in itemDiscount via orderItem (snapshot stays, change tracking)
 * - setAddress() - Set/replace address via order.customer
 * - removeAddress() - Remove address via order.customer
 *
 * Persistence API:
 * - getEntityData() - Raw data for persistence
 * - getCollectionData() - Collection data for persistence
 * - getRemovedIds() - IDs marked for deletion
 * - isMarkedForDeletion() - Check if aggregate is marked for deletion
 */
class Order extends EcommerceContext
{
    private ?OrderAggregate $aggregate = null;

    /** @var bool Flag to mark entire aggregate for deletion */
    private bool $markedForDeletion = false;

    /** @var array<string, array<int>> Tracks removed entity IDs by entity name */
    private array $removedIds = [
        'customer' => [],
        'orderItem' => [],
        'itemDiscount' => [],
        'address' => [],
    ];

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload,
        string $version,
        ?OrderAggregate $aggregate = null,
        int $depth = 0,
        bool $entry = true
    ) {
        parent::__construct($domainKernel, $payload, $version, $depth, $entry);
        $this->aggregate = $aggregate;
    }

    /**
     * Sets/replaces the root Order entity.
     * Pass null to mark entire aggregate for deletion.
     *
     * @param array<string, mixed>|null $data Root entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setOrder(?array $data): void
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
     * The next setOrder() is diffed against these values, so the
     * persist's expected-values check guards what the caller read, not what
     * was loaded just now. Call it BEFORE setOrder().
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
     * @return OrderAggregate Cloned aggregate data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getData(): OrderAggregate
    {
        /** @var OrderAggregate */
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
     * Sets/replaces the customer entity.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function setCustomer(array $data): void
    {
        $this->handle(namespace\Action\SetCustomer::class)($this->aggregate, $data);
    }

    /**
     * Adds a OrderItem to the orderItem collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addOrderItem(array $data): void
    {
        $this->handle(namespace\Action\AddOrderItem::class)($this->aggregate, $data);
    }

    /**
     * Removes an OrderItem from the orderItem collection.
     * Tracks the ID for later DELETE operation.
     * Includes CASCADE deletion of OWNED_ENTITY children.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeOrderItem(int $id): void
    {
        $removals = $this->handle(namespace\Action\RemoveOrderItem::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackCascadeDeletion($entityName, $entity);
        }
    }

    /**
     * Updates an OrderItem in the orderItem collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function updateOrderItem(int $id, array $data): void
    {
        $this->handle(namespace\Action\UpdateOrderItem::class)($this->aggregate, $id, $data);
    }

    /**
     * Adds a ItemDiscount to the itemDiscount collection.
     *
     * @param string $orderItemIdentifier Parent OrderItem identifier
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function addItemDiscount(string $orderItemIdentifier, array $data): ?ResponseStatus
    {
        return $this->handle(namespace\Action\AddItemDiscount::class)($this->aggregate, $orderItemIdentifier, $data);
    }

    /**
     * Removes an ItemDiscount from the itemDiscount collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function removeItemDiscount(int $id): ?ResponseStatus
    {
        $removals = $this->handle(namespace\Action\RemoveItemDiscount::class)($this->aggregate, $id);
        if ($removals instanceof ResponseStatus) {
            return $removals;
        }
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
        return null;
    }

    /**
     * Updates an ItemDiscount in the itemDiscount collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function updateItemDiscount(int $id, array $data): ?ResponseStatus
    {
        return $this->handle(namespace\Action\UpdateItemDiscount::class)($this->aggregate, $id, $data);
    }

    /**
     * Sets/replaces the address entity.
     *
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function setAddress(array $data): ?ResponseStatus
    {
        return $this->handle(namespace\Action\SetAddress::class)($this->aggregate, $data);
    }

    /**
     * Removes the address entity.
     * Tracks for later DELETE operation.
     * Nullifies DEPEND FK on parent before deletion.
     *
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function removeAddress(): ?ResponseStatus
    {
        $removals = $this->handle(namespace\Action\RemoveAddress::class)($this->aggregate);
        if ($removals instanceof ResponseStatus) {
            return $removals;
        }
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
        return null;
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
            'order' => [$this->aggregate, OrderAggregate::SOURCE, OrderAggregate::PRIMARY_KEY, ['id', 'created_at']],
            'customer' => [
                $this->aggregate->getCustomer(),
                Customer::SOURCE,
                Customer::PRIMARY_KEY,
                ['id', 'created_at']
            ],
            'address' => [
                $this->aggregate->getCustomer()?->getAddress(),
                Address::SOURCE,
                Address::PRIMARY_KEY,
                ['id']
            ],
            default => throw new InvalidArgumentException("Unknown entity: $entityName"),
        };

        $pkGetter = 'get' . ucfirst($pkColumn);
        $isNew = $entity === null || $entity->$pkGetter() === null;

        if ($isNew && $entity !== null) {
            if ($entityName === 'customer') {
                $hydration->hydrate($entity, [
                    'identifier' => $this->aggregate->getCustomer()?->getIdentifier()
                        ?? $this->handle(Identity::class)->generateUuid7(),
                ]);
            }
        }

        $onInserted = function (int|string $id) use ($hydration, $entity, $entityName, $pkColumn): void {
            if ($entity !== null) {
                $hydration->hydrate($entity, [$pkColumn => $id]);
            }
            if ($entityName === 'order') {
                foreach ($this->aggregate->getOrderItem() as $child) {
                    $hydration->hydrate($child, ['order_id' => $id]);
                }
            } elseif ($entityName === 'customer') {
                $dependent = $this->aggregate;
                if ($dependent !== null) {
                    $hydration->hydrate($dependent, ['customer_id' => $id]);
                }
            } elseif ($entityName === 'address') {
                $dependent = $this->aggregate?->getCustomer();
                if ($dependent !== null) {
                    $hydration->hydrate($dependent, ['billing_address_id' => $id]);
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
            'snapshot' => $entityName === 'order' && $entity !== null && !$isNew
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
            'orderItem' => array_map(function (object $entity) use ($hydration) {
                $pkGetter = 'get' . ucfirst(OrderItem::PRIMARY_KEY);
                $isNew = $entity->$pkGetter() === null;
                if ($isNew) {
                    $hydration->hydrate($entity, [
                        'identifier' => $entity->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
                        'order_id' => $this->aggregate->getId(),
                    ]);
                }
                return [
                    'table' => OrderItem::SOURCE,
                    'pkColumn' => OrderItem::PRIMARY_KEY,
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
                        $hydration->hydrate($entity, [OrderItem::PRIMARY_KEY => $id]);
                        foreach ($entity->getItemDiscount() as $child) {
                            $hydration->hydrate($child, ['item_id' => $id]);
                        }
                    },
                ];
            }, $this->aggregate->getOrderItem()),
            'itemDiscount' => (function () use ($hydration) {
                $result = [];
                foreach ($this->aggregate->getOrderItem() as $parent) {
                    foreach ($parent->getItemDiscount() as $entity) {
                        $pkGetter = 'get' . ucfirst(ItemDiscount::PRIMARY_KEY);
                        $isNew = $entity->$pkGetter() === null;
                        if ($isNew) {
                            $hydration->hydrate($entity, [
                                'item_id' => $parent->getId(),
                            ]);
                        }
                        $result[] = [
                            'table' => ItemDiscount::SOURCE,
                            'pkColumn' => ItemDiscount::PRIMARY_KEY,
                            'isNew' => $isNew,
                            'values' => $isNew ? array_filter(
                                $hydration->toArray($entity),
                                fn($value, $column) => $value !== null || !in_array($column, ['id'], true),
                                ARRAY_FILTER_USE_BOTH
                            ) : $hydration->getChanges($entity),
                            'expected' => $isNew
                                ? []
                                : array_intersect_key(
                                    $hydration->getSnapshot($entity),
                                    $hydration->getChanges($entity)
                                ),
                            'pkValue' => $entity->$pkGetter(),
                            'onInserted' => function (int|string $id) use ($hydration, $entity): void {
                                $hydration->hydrate($entity, [ItemDiscount::PRIMARY_KEY => $id]);
                            },
                        ];
                    }
                }
                return $result;
            })(),
            default => throw new InvalidArgumentException("Unknown collection: $collectionName"),
        };
    }

    /**
     * Helper method to track CASCADE deletion of OWNED_ENTITY children.
     *
     * @param string $entityName Entity property name
     * @param object|null $entity Entity to track with children
     */
    private function trackCascadeDeletion(string $entityName, ?object $entity): void
    {
        if ($entity === null) {
            return;
        }

        $this->trackDeletion($entityName, $entity);

        switch ($entityName) {
            case 'orderItem':
                foreach ($entity->getItemDiscount() as $item) {
                    $this->trackCascadeDeletion('itemDiscount', $item);
                }
                break;
        }
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
