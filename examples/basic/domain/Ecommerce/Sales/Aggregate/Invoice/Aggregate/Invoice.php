<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Aggregate;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity\Invoice as InvoiceAggregate;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity\InvoiceLine;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Invoice aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setInvoice() - Set/replace root entity
 *
 * OWNED_ENTITY methods:
 * - addInvoiceLine() - Add to invoiceLine collection
 * - removeInvoiceLine() - Remove from invoiceLine collection (tracks for DELETE)
 *
 * Persistence API:
 * - getEntityData() - Raw data for persistence
 * - getCollectionData() - Collection data for persistence
 * - getRemovedIds() - IDs marked for deletion
 * - isMarkedForDeletion() - Check if aggregate is marked for deletion
 */
class Invoice extends EcommerceContext
{
    private ?InvoiceAggregate $aggregate = null;

    /** @var bool Flag to mark entire aggregate for deletion */
    private bool $markedForDeletion = false;

    /** @var array<string, array<int>> Tracks removed entity IDs by entity name */
    private array $removedIds = [
        'invoiceLine' => [],
    ];

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload = null,
        string $version = '',
        ?InvoiceAggregate $aggregate = null
    ) {
        parent::__construct($domainKernel, $payload, $version);
        $this->aggregate = $aggregate;
    }

    /**
     * Sets/replaces the root Invoice entity.
     * Pass null to mark entire aggregate for deletion.
     *
     * @param array<string, mixed>|null $data Root entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setInvoice(?array $data): void
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
     * @return InvoiceAggregate Cloned aggregate data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getData(): InvoiceAggregate
    {
        /** @var InvoiceAggregate */
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
     * Adds a InvoiceLine to the invoiceLine collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addInvoiceLine(array $data): void
    {
        $this->handle(Action\AddInvoiceLine::class)($this->aggregate, $data);
    }

    /**
     * Removes an InvoiceLine from the invoiceLine collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeInvoiceLine(int $id): void
    {
        $removals = $this->handle(Action\RemoveInvoiceLine::class)($this->aggregate, $id);
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
            'invoice' => [$this->aggregate, InvoiceAggregate::SOURCE, InvoiceAggregate::PRIMARY_KEY],
            default => throw new InvalidArgumentException("Unknown entity: $entityName"),
        };

        $pkGetter = 'get' . ucfirst($pkColumn);
        $isNew = $entity === null || $entity->$pkGetter() === null;

        if ($isNew && $entity !== null) {
            if ($entityName === 'invoice') {
                $hydration->hydrate($entity, [
                    'identifier' => $entity->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
                ]);
            }
        }

        $onInserted = function (int|string $id) use ($hydration, $entity, $entityName, $pkColumn): void {
            if ($entity !== null) {
                $hydration->hydrate($entity, [$pkColumn => $id]);
            }
            if ($entityName === 'invoice') {
                foreach ($this->aggregate->getInvoiceLine() as $child) {
                    $hydration->hydrate($child, ['invoice_id' => $id]);
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
            'invoiceLine' => array_map(function (object $entity) use ($hydration) {
                $pkGetter = 'get' . ucfirst(InvoiceLine::PRIMARY_KEY);
                $isNew = $entity->$pkGetter() === null;
                if ($isNew) {
                    $hydration->hydrate($entity, [
                        'identifier' => $entity->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
                        'invoice_id' => $this->aggregate->getId(),
                    ]);
                }
                return [
                    'table' => InvoiceLine::SOURCE,
                    'pkColumn' => InvoiceLine::PRIMARY_KEY,
                    'isNew' => $isNew,
                    'values' => $isNew ? $hydration->toArray($entity) : $hydration->getChanges($entity),
                    'pkValue' => $entity->$pkGetter(),
                    'onInserted' => function (int|string $id) use ($hydration, $entity): void {
                        $hydration->hydrate($entity, [InvoiceLine::PRIMARY_KEY => $id]);
                    },
                ];
            }, $this->aggregate->getInvoiceLine()),
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
