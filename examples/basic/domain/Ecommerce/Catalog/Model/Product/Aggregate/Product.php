<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Aggregate;

use Ecommerce\Catalog\Model\Product\Aggregate\Entity\Product as ProductAggregate;
use Ecommerce\Catalog\Model\Product\Aggregate\Entity\ProductImage;
use Ecommerce\Catalog\Model\Product\Aggregate\Entity\ProductVariant;
use Ecommerce\Catalog\Model\Product\Aggregate\Entity\ProductVariantPrice;
use Ecommerce\EcommerceActor;
use Ecommerce\EcommerceContext;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Product aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setProduct() - Set/replace root entity
 * - overlayReadBasis() - Lay the caller's read basis over the root snapshot (CAS)
 *
 * OWNED_ENTITY methods:
 * - addProductImage() - Add to productImage collection
 * - removeProductImage() - Remove from productImage collection (tracks for DELETE)
 * - updateProductImage() - Update in productImage collection (snapshot stays, change tracking)
 * - addProductVariant() - Add to productVariant collection
 * - removeProductVariant() - Remove from productVariant collection (tracks for DELETE)
 * - updateProductVariant() - Update in productVariant collection (snapshot stays, change tracking)
 * - addProductVariantPrice() - Add to productVariantPrice via productVariant
 * - removeProductVariantPrice() - Remove from productVariantPrice via productVariant
 * - updateProductVariantPrice() - Update in productVariantPrice via productVariant (snapshot stays, change tracking)
 *
 * Persistence API:
 * - getEntityData() - Raw data for persistence
 * - getCollectionData() - Collection data for persistence
 * - getRemovedIds() - IDs marked for deletion
 * - isMarkedForDeletion() - Check if aggregate is marked for deletion
 */
class Product extends EcommerceContext
{
    private ?ProductAggregate $aggregate = null;

    /** @var bool Flag to mark entire aggregate for deletion */
    private bool $markedForDeletion = false;

    /** @var array<string, array<int>> Tracks removed entity IDs by entity name */
    private array $removedIds = [
        'productImage' => [],
        'productVariant' => [],
        'productVariantPrice' => [],
    ];

    public function __construct(
        DomainKernelInterface $domainKernel,
        mixed $payload,
        string $version,
        EcommerceActor $actor,
        ?ProductAggregate $aggregate = null,
        int $depth = 0,
        bool $entry = true
    ) {
        parent::__construct($domainKernel, $payload, $version, $actor, $depth, $entry);
        $this->aggregate = $aggregate;
    }

    /**
     * Sets/replaces the root Product entity.
     * Pass null to mark entire aggregate for deletion.
     *
     * @param array<string, mixed>|null $data Root entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function setProduct(?array $data): void
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
     * The next setProduct() is diffed against these values, so the
     * persist's expected-values check guards what the caller read, not what
     * was loaded just now. Call it BEFORE setProduct().
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
     * @return ProductAggregate Cloned aggregate data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getData(): ProductAggregate
    {
        /** @var ProductAggregate */
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
     * Adds a ProductImage to the productImage collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addProductImage(array $data): void
    {
        $this->handle(namespace\Action\AddProductImage::class)($this->aggregate, $data);
    }

    /**
     * Removes a ProductImage from the productImage collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeProductImage(int $id): void
    {
        $removals = $this->handle(namespace\Action\RemoveProductImage::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Updates a ProductImage in the productImage collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function updateProductImage(int $id, array $data): void
    {
        $this->handle(namespace\Action\UpdateProductImage::class)($this->aggregate, $id, $data);
    }

    /**
     * Adds a ProductVariant to the productVariant collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addProductVariant(array $data): void
    {
        $this->handle(namespace\Action\AddProductVariant::class)($this->aggregate, $data);
    }

    /**
     * Removes a ProductVariant from the productVariant collection.
     * Tracks the ID for later DELETE operation.
     * Includes CASCADE deletion of OWNED_ENTITY children.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeProductVariant(int $id): void
    {
        $removals = $this->handle(namespace\Action\RemoveProductVariant::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackCascadeDeletion($entityName, $entity);
        }
    }

    /**
     * Updates a ProductVariant in the productVariant collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function updateProductVariant(int $id, array $data): void
    {
        $this->handle(namespace\Action\UpdateProductVariant::class)($this->aggregate, $id, $data);
    }

    /**
     * Adds a ProductVariantPrice to the productVariantPrice collection.
     *
     * @param string $productVariantIdentifier Parent ProductVariant identifier
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function addProductVariantPrice(string $productVariantIdentifier, array $data): ?ResponseStatus
    {
        return $this->handle(namespace\Action\AddProductVariantPrice::class)(
            $this->aggregate,
            $productVariantIdentifier,
            $data
        );
    }

    /**
     * Removes a ProductVariantPrice from the productVariantPrice collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function removeProductVariantPrice(int $id): ?ResponseStatus
    {
        $removals = $this->handle(namespace\Action\RemoveProductVariantPrice::class)($this->aggregate, $id);
        if ($removals instanceof ResponseStatus) {
            return $removals;
        }
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
        return null;
    }

    /**
     * Updates a ProductVariantPrice in the productVariantPrice collection.
     * Applies the data without touching the snapshot, so persist writes only the changed columns.
     *
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws Throwable
     */
    public function updateProductVariantPrice(int $id, array $data): ?ResponseStatus
    {
        return $this->handle(namespace\Action\UpdateProductVariantPrice::class)($this->aggregate, $id, $data);
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
            'product' => [$this->aggregate, ProductAggregate::SOURCE, ProductAggregate::PRIMARY_KEY, ['id']],
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
            foreach ($this->aggregate->getProductImage() as $child) {
                $hydration->hydrate($child, ['product_id' => $id]);
            }
            foreach ($this->aggregate->getProductVariant() as $child) {
                $hydration->hydrate($child, ['product_id' => $id]);
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
            'productImage' => array_map(function (object $entity) use ($hydration) {
                $pkGetter = 'get' . ucfirst(ProductImage::PRIMARY_KEY);
                $isNew = $entity->$pkGetter() === null;
                if ($isNew) {
                    $hydration->hydrate($entity, [
                        'product_id' => $this->aggregate->getId(),
                    ]);
                }
                return [
                    'table' => ProductImage::SOURCE,
                    'pkColumn' => ProductImage::PRIMARY_KEY,
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
                        $hydration->hydrate($entity, [ProductImage::PRIMARY_KEY => $id]);
                    },
                ];
            }, $this->aggregate->getProductImage()),
            'productVariant' => array_map(function (object $entity) use ($hydration) {
                $pkGetter = 'get' . ucfirst(ProductVariant::PRIMARY_KEY);
                $isNew = $entity->$pkGetter() === null;
                if ($isNew) {
                    $hydration->hydrate($entity, [
                        'identifier' => $entity->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
                        'product_id' => $this->aggregate->getId(),
                    ]);
                }
                return [
                    'table' => ProductVariant::SOURCE,
                    'pkColumn' => ProductVariant::PRIMARY_KEY,
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
                        $hydration->hydrate($entity, [ProductVariant::PRIMARY_KEY => $id]);
                        foreach ($entity->getProductVariantPrice() as $child) {
                            $hydration->hydrate($child, ['variant_id' => $id]);
                        }
                    },
                ];
            }, $this->aggregate->getProductVariant()),
            'productVariantPrice' => (function () use ($hydration) {
                $result = [];
                foreach ($this->aggregate->getProductVariant() as $parent) {
                    foreach ($parent->getProductVariantPrice() as $entity) {
                        $pkGetter = 'get' . ucfirst(ProductVariantPrice::PRIMARY_KEY);
                        $isNew = $entity->$pkGetter() === null;
                        if ($isNew) {
                            $hydration->hydrate($entity, [
                                'variant_id' => $parent->getId(),
                            ]);
                        }
                        $result[] = [
                            'table' => ProductVariantPrice::SOURCE,
                            'pkColumn' => ProductVariantPrice::PRIMARY_KEY,
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
                                $hydration->hydrate($entity, [ProductVariantPrice::PRIMARY_KEY => $id]);
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
            case 'productVariant':
                foreach ($entity->getProductVariantPrice() as $item) {
                    $this->trackCascadeDeletion('productVariantPrice', $item);
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
