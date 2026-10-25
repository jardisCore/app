<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Aggregate;

use Ecommerce\Catalog\Model\Category\Aggregate\Entity\Category as CategoryAggregate;
use Ecommerce\Catalog\Model\Category\Aggregate\Entity\CategoryTranslation;
use Ecommerce\EcommerceContext;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Category aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setCategory() - Set/replace root entity
 * - overlayReadBasis() - Lay the caller's read basis over the root snapshot (CAS)
 *
 * OWNED_ENTITY methods:
 * - addCategoryTranslation() - Add to categoryTranslation collection
 * - removeCategoryTranslation() - Remove from categoryTranslation collection (tracks for DELETE)
 * - updateCategoryTranslation() - Update in categoryTranslation collection (snapshot stays, change tracking)
 *
 * Persistence API:
 * - getEntityData() - Raw data for persistence
 * - getCollectionData() - Collection data for persistence
 * - getRemovedIds() - IDs marked for deletion
 * - isMarkedForDeletion() - Check if aggregate is marked for deletion
 */
class Category extends EcommerceContext
{
    private ?CategoryAggregate $aggregate = null;

    /** @var bool Flag to mark entire aggregate for deletion */
    private bool $markedForDeletion = false;

    /** @var array<string, array<int>> Tracks removed entity IDs by entity name */
    private array $removedIds = [
        'categoryTranslation' => [],
    ];

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload,
        string $version,
        ?CategoryAggregate $aggregate = null,
        int $depth = 0,
        bool $entry = true
    ) {
        parent::__construct($domainKernel, $payload, $version, $depth, $entry);
        $this->aggregate = $aggregate;
    }

    /**
     * Sets/replaces the root Category entity.
     * Pass null to mark entire aggregate for deletion.
     *
     * @param array<string, mixed>|null $data Root entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setCategory(?array $data): void
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
     * The next setCategory() is diffed against these values, so the
     * persist's expected-values check guards what the caller read, not what
     * was loaded just now. Call it BEFORE setCategory().
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
     * @return CategoryAggregate Cloned aggregate data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getData(): CategoryAggregate
    {
        /** @var CategoryAggregate */
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
     * Adds a CategoryTranslation to the categoryTranslation collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addCategoryTranslation(array $data): void
    {
        $this->handle(namespace\Action\AddCategoryTranslation::class)($this->aggregate, $data);
    }

    /**
     * Removes a CategoryTranslation from the categoryTranslation collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeCategoryTranslation(int $id): void
    {
        $removals = $this->handle(namespace\Action\RemoveCategoryTranslation::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Updates a CategoryTranslation in the categoryTranslation collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function updateCategoryTranslation(int $id, array $data): void
    {
        $this->handle(namespace\Action\UpdateCategoryTranslation::class)($this->aggregate, $id, $data);
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
            'category' => [
                $this->aggregate,
                CategoryAggregate::SOURCE,
                CategoryAggregate::PRIMARY_KEY,
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
            foreach ($this->aggregate->getCategoryTranslation() as $child) {
                $hydration->hydrate($child, ['category_id' => $id]);
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
            'categoryTranslation' => array_map(function (object $entity) use ($hydration) {
                $pkGetter = 'get' . ucfirst(CategoryTranslation::PRIMARY_KEY);
                $isNew = $entity->$pkGetter() === null;
                if ($isNew) {
                    $hydration->hydrate($entity, [
                        'category_id' => $this->aggregate->getId(),
                    ]);
                }
                return [
                    'table' => CategoryTranslation::SOURCE,
                    'pkColumn' => CategoryTranslation::PRIMARY_KEY,
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
                        $hydration->hydrate($entity, [CategoryTranslation::PRIMARY_KEY => $id]);
                    },
                ];
            }, $this->aggregate->getCategoryTranslation()),
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
