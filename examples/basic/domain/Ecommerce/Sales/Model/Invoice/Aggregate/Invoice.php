<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Aggregate;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Entity\Invoice as InvoiceAggregate;
use Ecommerce\Sales\Model\Invoice\Aggregate\Entity\InvoiceLine;
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
 * - overlayReadBasis() - Lay the caller's read basis over the root snapshot (CAS)
 *
 * OWNED_ENTITY methods:
 * - addInvoiceLine() - Add to invoiceLine collection
 * - removeInvoiceLine() - Remove from invoiceLine collection (tracks for DELETE)
 * - updateInvoiceLine() - Update in invoiceLine collection (snapshot stays, change tracking)
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
        mixed $payload,
        string $version,
        ?InvoiceAggregate $aggregate = null,
        int $depth = 0,
        bool $entry = true
    ) {
        parent::__construct($domainKernel, $payload, $version, $depth, $entry);
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
     * Lays the read basis of the caller over the root snapshot.
     *
     * The next setInvoice() is diffed against these values, so the
     * persist's expected-values check guards what the caller read, not what
     * was loaded just now. Call it BEFORE setInvoice().
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
        $this->handle(namespace\Action\AddInvoiceLine::class)($this->aggregate, $data);
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
        $removals = $this->handle(namespace\Action\RemoveInvoiceLine::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Updates an InvoiceLine in the invoiceLine collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function updateInvoiceLine(int $id, array $data): void
    {
        $this->handle(namespace\Action\UpdateInvoiceLine::class)($this->aggregate, $id, $data);
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
            'invoice' => [
                $this->aggregate,
                InvoiceAggregate::SOURCE,
                InvoiceAggregate::PRIMARY_KEY,
                ['id', 'created_at']
            ],
            default => throw new InvalidArgumentException("Unknown entity: $entityName"),
        };

        $pkGetter = 'get' . ucfirst($pkColumn);
        $isNew = $entity === null || $entity->$pkGetter() === null;

        if ($isNew && $entity !== null) {
            $hydration->hydrate($entity, [
                'identifier' => $this->aggregate->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
            ]);
        }

        $onInserted = function (int|string $id) use ($hydration, $entity, $pkColumn): void {
            if ($entity !== null) {
                $hydration->hydrate($entity, [$pkColumn => $id]);
            }
            foreach ($this->aggregate->getInvoiceLine() as $child) {
                $hydration->hydrate($child, ['invoice_id' => $id]);
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
            'snapshot' => $entity !== null && !$isNew ? $hydration->getSnapshot($entity) : [],
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
                    'values' => $isNew ? array_filter(
                        $hydration->toArray($entity),
                        fn($value, $column) => $value !== null || !in_array($column, ['id', 'created_at'], true),
                        ARRAY_FILTER_USE_BOTH
                    ) : $hydration->getChanges($entity),
                    'expected' => $isNew
                        ? []
                        : array_intersect_key($hydration->getSnapshot($entity), $hydration->getChanges($entity)),
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
