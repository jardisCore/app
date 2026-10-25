<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Aggregate;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\Entity\PriceTier as PriceTierAggregate;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for PriceTier aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setPriceTier() - Set/replace root entity
 * - overlayReadBasis() - Lay the caller's read basis over the root snapshot (CAS)
 *
 * OWNED_ENTITY methods:
 *
 * Persistence API:
 * - getEntityData() - Raw data for persistence
 * - getRemovedIds() - IDs marked for deletion
 * - isMarkedForDeletion() - Check if aggregate is marked for deletion
 */
class PriceTier extends EcommerceContext
{
    private ?PriceTierAggregate $aggregate = null;

    /** @var bool Flag to mark entire aggregate for deletion */
    private bool $markedForDeletion = false;

    /** @var array<string, array<int>> Tracks removed entity IDs by entity name */
    private array $removedIds = [];

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload,
        string $version,
        ?PriceTierAggregate $aggregate = null,
        int $depth = 0,
        bool $entry = true
    ) {
        parent::__construct($domainKernel, $payload, $version, $depth, $entry);
        $this->aggregate = $aggregate;
    }

    /**
     * Sets/replaces the root PriceTier entity.
     * Pass null to mark entire aggregate for deletion.
     *
     * @param array<string, mixed>|null $data Root entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setPriceTier(?array $data): void
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
     * The next setPriceTier() is diffed against these values, so the
     * persist's expected-values check guards what the caller read, not what
     * was loaded just now. Call it BEFORE setPriceTier().
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
     * @return PriceTierAggregate Cloned aggregate data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getData(): PriceTierAggregate
    {
        /** @var PriceTierAggregate */
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
            'priceTier' => [
                $this->aggregate,
                PriceTierAggregate::SOURCE,
                PriceTierAggregate::PRIMARY_KEY,
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
