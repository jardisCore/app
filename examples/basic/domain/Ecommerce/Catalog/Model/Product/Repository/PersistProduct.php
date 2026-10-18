<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Repository;

use Ecommerce\Catalog\Model\Product\Aggregate\Entity\ProductImage;
use Ecommerce\Catalog\Model\Product\Aggregate\Entity\ProductVariant;
use Ecommerce\Catalog\Model\Product\Aggregate\Entity\ProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Aggregate\Product as ProductHandler;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\DbConnection\DbConnectionInterface;
use JardisSupport\Contract\DbConnection\SavepointInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Contract\Repository\Exception\UniqueViolationException;
use JardisSupport\Repository\Adapter\PdoConnection;
use JardisSupport\Repository\Repository;
use PDOException;
use Throwable;

/**
 * Persists Product with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistProduct extends EcommerceContext
{
    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param ProductHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(ProductHandler $handler): ContextResponseInterface
    {
        if ($handler->isMarkedForDeletion()) {
            $this->deleteAggregate($handler);
            return $this->result();
        }

        $writer = $this->resolveWriter();
        $ownsTransaction = !$writer->inTransaction();
        if ($ownsTransaction) {
            $writer->beginTransaction();
        } else {
            $writer->savepoint('jardis_persist_product');
        }

        $isNew = false;
        $now = $this->clock()->now();
        try {
            $isNew = $this->persistProduct($handler, $now);
            $this->persistProductImage($handler, $now);
            $this->persistProductVariant($handler, $now);
            $this->persistProductVariantPrice($handler, $now);

            $this->deleteRemovedEntities($handler);

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_product');
            }

            return $this->result();
        } catch (UniqueViolationException $violation) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_product');
                $writer->releaseSavepoint('jardis_persist_product');
            }
            throw new ConcurrencyConflictException(
                'duplicate',
                'Product',
                $this->conflictContext($handler),
                $this->violatedFields($violation)
            );
        } catch (TransientConflictException $transient) {
            if ($ownsTransaction) {
                if ($writer->inTransaction()) {
                    $writer->rollback();
                }
            } else {
                try {
                    $writer->rollbackToSavepoint('jardis_persist_product');
                    $writer->releaseSavepoint('jardis_persist_product');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_write',
                'Product',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_product');
                $writer->releaseSavepoint('jardis_persist_product');
            }
            throw $e;
        }
    }

    /**
     * Persists product entity data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @param \DateTimeImmutable $now The ONE server instant of this persist
     * @return bool Whether the entity is new
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistProduct(ProductHandler $handler, \DateTimeImmutable $now): bool
    {
        $productData = $handler->getEntityData('product');
        if ($productData['values'] !== []) {
            $productData['values'] = array_merge(
                $productData['values'],
                $productData['isNew']
                    ? ['created_at' => $now->format('Y-m-d H:i:s'), 'updated_at' => $now->format('Y-m-d H:i:s')]
                    : ['updated_at' => $now->format('Y-m-d H:i:s')]
            );
        }

        if ($productData['isNew']) {
            if (!empty($productData['values'])) {
                $newId = $this->getRepository()->insert(
                    $productData['table'],
                    $productData['pkColumn'],
                    $this->normalizeBoolValues($productData['values'])
                );
                ($productData['onInserted'])($newId);
            }
        } elseif (!empty($productData['values'])) {
            if (
                !$this->getRepository()->update(
                    $productData['table'],
                    $productData['pkColumn'],
                    $productData['pkValue'],
                    $this->normalizeBoolValues($productData['values']),
                    $this->normalizeBoolValues($productData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Product',
                    $this->conflictContext($handler)
                );
            }
        }

        return $productData['isNew'];
    }

    /**
     * Persists productImage collection data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @param \DateTimeImmutable $now The ONE server instant of this persist
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistProductImage(ProductHandler $handler, \DateTimeImmutable $now): void
    {
        foreach ($handler->getCollectionData('productImage') as $productImageData) {
            if ($productImageData['values'] !== []) {
                $productImageData['values'] = array_merge(
                    $productImageData['values'],
                    ['updated_at' => $now->format('Y-m-d H:i:s')]
                );
            }

            if ($productImageData['isNew']) {
                if (!empty($productImageData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $productImageData['table'],
                        $productImageData['pkColumn'],
                        $this->normalizeBoolValues($productImageData['values'])
                    );
                    ($productImageData['onInserted'])($newId);
                }
            } elseif (!empty($productImageData['values'])) {
                if (
                    !$this->getRepository()->update(
                        $productImageData['table'],
                        $productImageData['pkColumn'],
                        $productImageData['pkValue'],
                        $this->normalizeBoolValues($productImageData['values']),
                        $this->normalizeBoolValues($productImageData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Product',
                        $this->conflictContext($handler)
                    );
                }
            }
        }
    }

    /**
     * Persists productVariant collection data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @param \DateTimeImmutable $now The ONE server instant of this persist
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistProductVariant(ProductHandler $handler, \DateTimeImmutable $now): void
    {
        foreach ($handler->getCollectionData('productVariant') as $productVariantData) {
            if ($productVariantData['isNew']) {
                if (!empty($productVariantData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $productVariantData['table'],
                        $productVariantData['pkColumn'],
                        $this->normalizeBoolValues($productVariantData['values'])
                    );
                    ($productVariantData['onInserted'])($newId);
                }
            } elseif (!empty($productVariantData['values'])) {
                if (
                    !$this->getRepository()->update(
                        $productVariantData['table'],
                        $productVariantData['pkColumn'],
                        $productVariantData['pkValue'],
                        $this->normalizeBoolValues($productVariantData['values']),
                        $this->normalizeBoolValues($productVariantData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Product',
                        $this->conflictContext($handler)
                    );
                }
            }
        }
    }

    /**
     * Persists productVariantPrice collection data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @param \DateTimeImmutable $now The ONE server instant of this persist
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistProductVariantPrice(ProductHandler $handler, \DateTimeImmutable $now): void
    {
        foreach ($handler->getCollectionData('productVariantPrice') as $productVariantPriceData) {
            if ($productVariantPriceData['isNew']) {
                if (!empty($productVariantPriceData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $productVariantPriceData['table'],
                        $productVariantPriceData['pkColumn'],
                        $this->normalizeBoolValues($productVariantPriceData['values'])
                    );
                    ($productVariantPriceData['onInserted'])($newId);
                }
            } elseif (!empty($productVariantPriceData['values'])) {
                if (
                    !$this->getRepository()->update(
                        $productVariantPriceData['table'],
                        $productVariantPriceData['pkColumn'],
                        $productVariantPriceData['pkValue'],
                        $this->normalizeBoolValues($productVariantPriceData['values']),
                        $this->normalizeBoolValues($productVariantPriceData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Product',
                        $this->conflictContext($handler)
                    );
                }
            }
        }
    }

    /**
     * Deletes all tracked OWNED_ENTITY from the database.
     *
     * Delegates to Repository::deleteAll() for batch deletion.
     * Deletes are performed in FK-safe order.
     *
     * @param ProductHandler $handler Aggregate handler
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function deleteRemovedEntities(ProductHandler $handler): void
    {
        $removedIds = $handler->getRemovedIds();
        if (!empty($removedIds['productVariantPrice'])) {
            $this->getRepository()->deleteAll(
                ProductVariantPrice::SOURCE,
                ProductVariantPrice::PRIMARY_KEY,
                $removedIds['productVariantPrice']
            );
        }

        if (!empty($removedIds['productImage'])) {
            $this->getRepository()->deleteAll(
                ProductImage::SOURCE,
                ProductImage::PRIMARY_KEY,
                $removedIds['productImage']
            );
        }

        if (!empty($removedIds['productVariant'])) {
            $this->getRepository()->deleteAll(
                ProductVariant::SOURCE,
                ProductVariant::PRIMARY_KEY,
                $removedIds['productVariant']
            );
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param ProductHandler $handler The aggregate handler
     * @throws ConcurrencyConflictException
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     */
    protected function deleteAggregate(ProductHandler $handler): void
    {
        $writer = $this->resolveWriter();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('product');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        } else {
            $writer->savepoint('jardis_persist_product');
        }

        try {
            $productVariantPriceData = $handler->getCollectionData('productVariantPrice');
            $productVariantPriceIds = array_unique(array_filter(array_column($productVariantPriceData, 'pkValue')));
            if (!empty($productVariantPriceIds)) {
                $this->getRepository()->deleteAll(
                    ProductVariantPrice::SOURCE,
                    ProductVariantPrice::PRIMARY_KEY,
                    $productVariantPriceIds
                );
            }

            $productImageData = $handler->getCollectionData('productImage');
            $productImageIds = array_unique(array_filter(array_column($productImageData, 'pkValue')));
            if (!empty($productImageIds)) {
                $this->getRepository()->deleteAll(ProductImage::SOURCE, ProductImage::PRIMARY_KEY, $productImageIds);
            }

            $productVariantData = $handler->getCollectionData('productVariant');
            $productVariantIds = array_unique(array_filter(array_column($productVariantData, 'pkValue')));
            if (!empty($productVariantIds)) {
                $this->getRepository()->deleteAll(
                    ProductVariant::SOURCE,
                    ProductVariant::PRIMARY_KEY,
                    $productVariantIds
                );
            }

            if ($aggregateId !== null) {
                if (
                    !$this->getRepository()->delete(
                        $rootData['table'],
                        $rootData['pkColumn'],
                        $aggregateId,
                        $this->normalizeBoolValues($rootData['snapshot'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_delete',
                        'Product',
                        $this->conflictContext($handler)
                    );
                }
            }

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_product');
            }
        } catch (TransientConflictException $transient) {
            if ($ownsTransaction) {
                if ($writer->inTransaction()) {
                    $writer->rollback();
                }
            } else {
                try {
                    $writer->rollbackToSavepoint('jardis_persist_product');
                    $writer->releaseSavepoint('jardis_persist_product');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_delete',
                'Product',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_product');
                $writer->releaseSavepoint('jardis_persist_product');
            }
            throw $e;
        }
    }

    /**
     * Context of every 409 of this aggregate: the outer-door identifier of the
     * root (`id` only without a public unique key), never FK or child internals.
     *
     * @param ProductHandler $handler Aggregate handler
     * @return array<string, mixed>
     */
    private function conflictContext(ProductHandler $handler): array
    {
        return ['identifier' => $handler->getData()->getIdentifier()];
    }

    /**
     * Outer-door names of the fields a unique violation hit (`violated` of the 409
     * `duplicate`): root fields by name, child fields as `<kindProperty>.<field>`;
     * columns without an outer name are left out, unknown columns/table give [].
     *
     * @return list<string>
     */
    private function violatedFields(UniqueViolationException $e): array
    {
        $names = match ($e->getTable()) {
            'product_images' => [
                'alt_text' => 'productImage.altText',
                'file_size' => 'productImage.fileSize',
                'height' => 'productImage.height',
                'is_primary' => 'productImage.isPrimary',
                'mime_type' => 'productImage.mimeType',
                'sort_order' => 'productImage.sortOrder',
                'url' => 'productImage.url',
                'width' => 'productImage.width',
            ],
            'product_variant_prices' => [
                'currency' => 'productVariantPrice.currency',
                'price' => 'productVariantPrice.price',
                'region' => 'productVariantPrice.region',
                'valid_from' => 'productVariantPrice.validFrom',
                'valid_until' => 'productVariantPrice.validUntil',
            ],
            'product_variants' => [
                'is_available' => 'productVariant.isAvailable',
                'low_stock_threshold' => 'productVariant.lowStockThreshold',
                'option_name' => 'productVariant.optionName',
                'option_value' => 'productVariant.optionValue',
                'price_modifier' => 'productVariant.priceModifier',
                'sku' => 'productVariant.sku',
                'sort_order' => 'productVariant.sortOrder',
                'stock_quantity' => 'productVariant.stockQuantity',
                'variant_name' => 'productVariant.variantName',
                'weight_grams' => 'productVariant.weightGrams',
            ],
            'products' => [
                'compare_at_price' => 'compareAtPrice',
                'cost_price' => 'costPrice',
                'currency' => 'currency',
                'description' => 'description',
                'identifier' => 'identifier',
                'is_active' => 'isActive',
                'is_featured' => 'isFeatured',
                'name' => 'productName',
                'price' => 'price',
                'short_description' => 'shortDescription',
                'sku' => 'sku',
                'slug' => 'slug',
                'tax_class' => 'taxClass',
                'weight_grams' => 'weightGrams',
            ],
            default => [],
        };

        $fields = [];
        foreach ($e->getColumns() as $column) {
            if (isset($names[$column])) {
                $fields[] = $names[$column];
            }
        }

        return $fields;
    }

    /**
     * Gets Repository service (shared via Factory).
     *
     * @return Repository
     * @throws \RuntimeException
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
     * Resolves the write connection as a savepoint-capable connection.
     *
     * Handles ConnectionPoolInterface (its writer) and plain PDO (wrapped).
     *
     * @throws \RuntimeException If no database connection is configured or its writer cannot hold savepoints
     */
    protected function resolveWriter(): DbConnectionInterface&SavepointInterface
    {
        $connection = $this->resource()->dbConnection();

        if ($connection instanceof \PDO) {
            return new PdoConnection($connection);
        }

        if ($connection instanceof ConnectionPoolInterface) {
            $writer = $connection->getWriter();

            if ($writer instanceof SavepointInterface) {
                return $writer;
            }

            throw new \RuntimeException('The write connection does not support savepoints');
        }

        throw new \RuntimeException('No database connection configured');
    }

    /**
     * Resolves the database connection to a PDO instance (the writer's own).
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        return $this->resolveWriter()->pdo();
    }
}
