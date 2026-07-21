<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate;

use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductImage;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductVariantPrice;
use Ecommerce\EcommerceContext;
use InvalidArgumentException;
use JardisSupport\Contract\Kernel\DomainKernelInterface;
use JardisSupport\Data\Hydration;
use JardisSupport\Data\Identity;
use ReflectionException;
use Throwable;

/**
 * Smart protective layer for Product aggregate.
 *
 * Provides business logic methods for aggregate management:
 * - setProduct() - Set/replace root entity
 *
 * OWNED_ENTITY methods:
 * - addProductImage() - Add to productImage collection
 * - removeProductImage() - Remove from productImage collection (tracks for DELETE)
 * - addProductVariant() - Add to productVariant collection
 * - removeProductVariant() - Remove from productVariant collection (tracks for DELETE)
 * - addProductVariantPrice() - Add to productVariantPrice via productVariant
 * - removeProductVariantPrice() - Remove from productVariantPrice via productVariant
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
        mixed $payload = null,
        string $version = '',
        ?ProductAggregate $aggregate = null
    ) {
        parent::__construct($domainKernel, $payload, $version);
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
        $this->handle(Action\AddProductImage::class)($this->aggregate, $data);
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
        $removals = $this->handle(Action\RemoveProductImage::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackDeletion($entityName, $entity);
        }
    }

    /**
     * Adds a ProductVariant to the productVariant collection.
     *
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addProductVariant(array $data): void
    {
        $this->handle(Action\AddProductVariant::class)($this->aggregate, $data);
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
        $removals = $this->handle(Action\RemoveProductVariant::class)($this->aggregate, $id);
        foreach ($removals as [$entityName, $entity]) {
            $this->trackCascadeDeletion($entityName, $entity);
        }
    }

    /**
     * Adds a ProductVariantPrice to the productVariantPrice collection.
     *
     * @param string $productVariantIdentifier Parent ProductVariant identifier
     * @param array<string, mixed> $data Entity data
     * @throws Throwable
     */
    public function addProductVariantPrice(string $productVariantIdentifier, array $data): void
    {
        $this->handle(Action\AddProductVariantPrice::class)($this->aggregate, $productVariantIdentifier, $data);
    }

    /**
     * Removes a ProductVariantPrice from the productVariantPrice collection.
     * Tracks the ID for later DELETE operation.
     *
     * @param int $id
     * @throws Throwable
     */
    public function removeProductVariantPrice(int $id): void
    {
        $removals = $this->handle(Action\RemoveProductVariantPrice::class)($this->aggregate, $id);
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
            'product' => [$this->aggregate, ProductAggregate::SOURCE, ProductAggregate::PRIMARY_KEY],
            default => throw new InvalidArgumentException("Unknown entity: $entityName"),
        };

        $pkGetter = 'get' . ucfirst($pkColumn);
        $isNew = $entity === null || $entity->$pkGetter() === null;

        if ($isNew && $entity !== null) {
            if ($entityName === 'product') {
                $hydration->hydrate($entity, [
                    'identifier' => $entity->getIdentifier() ?? $this->handle(Identity::class)->generateUuid7(),
                ]);
            }
        }

        $onInserted = function (int|string $id) use ($hydration, $entity, $entityName, $pkColumn): void {
            if ($entity !== null) {
                $hydration->hydrate($entity, [$pkColumn => $id]);
            }
            if ($entityName === 'product') {
                foreach ($this->aggregate->getProductImage() as $child) {
                    $hydration->hydrate($child, ['product_id' => $id]);
                }
                foreach ($this->aggregate->getProductVariant() as $child) {
                    $hydration->hydrate($child, ['product_id' => $id]);
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
                    'values' => $isNew ? $hydration->toArray($entity) : $hydration->getChanges($entity),
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
                    'values' => $isNew ? $hydration->toArray($entity) : $hydration->getChanges($entity),
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
                            'values' => $isNew ? $hydration->toArray($entity) : $hydration->getChanges($entity),
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
