<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Address;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Customer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\ItemDiscount;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\OrderItem;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Order aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setOrder() - Set/replace root entity
 *
 * OWNED_ENTITY methods:
 * - setCustomer() - Set/replace customer
 * - removeCustomer() - Remove customer
 * - addOrderItem() - Add to orderItem collection
 * - removeOrderItem() - Remove from orderItem collection (tracks for DELETE)
 * - addItemDiscount() - Add to itemDiscount via orderItem
 * - removeItemDiscount() - Remove from itemDiscount via orderItem
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
        mixed $payload = null,
        string $version = '',
        ?OrderAggregate $aggregate = null
    ) {
        parent::__construct($domainKernel, $payload, $version);
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
        $this->handle(Action\SetCustomer::class)($this->aggregate, $data);
    }

    /**
     * Removes the customer entity.
     * Tracks for later DELETE operation.
     * Includes CASCADE deletion of OWNED_ENTITY children.
     * Nullifies DEPEND FK on parent before deletion.
     * @throws Throwable
     */
    public function removeCustomer(): void
    {
        $removals = $this->handle(Action\RemoveCustomer::class)($this->aggregate);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackCascadeDeletion($entityName, $entity);
        }
    }

    /**
     * Adds a OrderItem to the orderItem collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addOrderItem(array $data): void
    {
        $this->handle(Action\AddOrderItem::class)($this->aggregate, $data);
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
        $removals = $this->handle(Action\RemoveOrderItem::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackCascadeDeletion($entityName, $entity);
        }
    }

    /**
     * Adds a ItemDiscount to the itemDiscount collection.
     *
     * @param string $orderItemIdentifier Parent OrderItem identifier
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addItemDiscount(string $orderItemIdentifier, array $data): void
    {
        $this->handle(Action\AddItemDiscount::class)($this->aggregate, $orderItemIdentifier, $data);
    }

    /**
     * Removes an ItemDiscount from the itemDiscount collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeItemDiscount(int $id): void
    {
        $removals = $this->handle(Action\RemoveItemDiscount::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Sets/replaces the address entity.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function setAddress(array $data): void
    {
        $this->handle(Action\SetAddress::class)($this->aggregate, $data);
    }

    /**
     * Removes the address entity.
     * Tracks for later DELETE operation.
     * Nullifies DEPEND FK on parent before deletion.
     *
     * @throws Throwable
     */
    public function removeAddress(): void
    {
        $removals = $this->handle(Action\RemoveAddress::class)($this->aggregate);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Returns entity data as array for persistence.
     *
     * For new entities returns all values (toArray).
     * For existing entities returns only changed values.
     *
     * @param string $entityName Entity property name
     * @return array{table: string, pkColumn: string, isNew: bool, values: array<string, mixed>, pkValue: int|string|null, onInserted: \Closure}
     * @throws InvalidArgumentException If entity name is unknown
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getEntityData(string $entityName): array
    {
        $hydration = $this->getHydration();

        [$entity, $table, $pkColumn] = match ($entityName) {
            'order' => [$this->aggregate, OrderAggregate::SOURCE, OrderAggregate::PRIMARY_KEY],
            'customer' => [$this->aggregate->getCustomer(), Customer::SOURCE, Customer::PRIMARY_KEY],
            'address' => [$this->aggregate->getCustomer()?->getAddress(), Address::SOURCE, Address::PRIMARY_KEY],
            default => throw new InvalidArgumentException("Unknown entity: $entityName"),
        };

        $pkGetter = 'get' . ucfirst($pkColumn);
        $isNew = $entity === null || $entity->$pkGetter() === null;

        if ($isNew && $entity !== null) {
            if ($entityName === 'customer') {
                $hydration->hydrate($entity, [
                    'identifier' => $entity->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
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
            'values' => $entity !== null ? ($isNew ? $hydration->toArray($entity) : $hydration->getChanges($entity)) : [],
            'pkValue' => $entity !== null ? $entity->$pkGetter() : null,
            'onInserted' => $onInserted,
        ];
    }

    /**
     * Returns collection data as array of entity data arrays.
     *
     * @param string $collectionName Collection property name
     * @return array<int, array{table: string, pkColumn: string, isNew: bool, values: array<string, mixed>, pkValue: int|string|null, onInserted: \Closure}>
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
                    'values' => $isNew ? $hydration->toArray($entity) : $hydration->getChanges($entity),
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
                            'values' => $isNew ? $hydration->toArray($entity) : $hydration->getChanges($entity),
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
            case 'customer':
                $child = $entity->getAddress();
                if ($child !== null) {
                    $this->trackCascadeDeletion('address', $child);
                }
                break;
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
