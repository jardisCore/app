<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Repository;

use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductImage;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductVariantPrice;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Repository\Repository;
use PDOException;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product as ProductHandler;

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
     * @throws Exception
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(ProductHandler $handler): ContextResponseInterface
    {
        if ($handler->isMarkedForDeletion()) {
            $this->deleteAggregate($handler);
            return $this->result();
        }

        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();
        if ($ownsTransaction) {
            $writer->beginTransaction();
        }

        $isNew = false;
        try {
            $isNew = $this->persistProduct($handler);
            $this->persistProductImage($handler);
            $this->persistProductVariant($handler);
            $this->persistProductVariantPrice($handler);

            $this->deleteRemovedEntities($handler);

            if ($ownsTransaction) {
                $writer->commit();
            }

            return $this->result();
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            }
            throw $e;
        }
    }

    /**
     * Persists product entity data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistProduct(ProductHandler $handler): bool
    {
        $productData = $handler->getEntityData('product');
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
            $this->getRepository()->update(
                $productData['table'],
                $productData['pkColumn'],
                $productData['pkValue'],
                $this->normalizeBoolValues($productData['values'])
            );
        }

        return $productData['isNew'];
    }

    /**
     * Persists productImage collection data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistProductImage(ProductHandler $handler): void
    {
        foreach ($handler->getCollectionData('productImage') as $productImageData) {
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
                $this->getRepository()->update(
                    $productImageData['table'],
                    $productImageData['pkColumn'],
                    $productImageData['pkValue'],
                    $this->normalizeBoolValues($productImageData['values'])
                );
            }
        }
    }

    /**
     * Persists productVariant collection data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistProductVariant(ProductHandler $handler): void
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
                $this->getRepository()->update(
                    $productVariantData['table'],
                    $productVariantData['pkColumn'],
                    $productVariantData['pkValue'],
                    $this->normalizeBoolValues($productVariantData['values'])
                );
            }
        }
    }

    /**
     * Persists productVariantPrice collection data.
     *
     * @param ProductHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistProductVariantPrice(ProductHandler $handler): void
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
                $this->getRepository()->update(
                    $productVariantPriceData['table'],
                    $productVariantPriceData['pkColumn'],
                    $productVariantPriceData['pkValue'],
                    $this->normalizeBoolValues($productVariantPriceData['values'])
                );
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
     * @throws RuntimeException
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
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteAggregate(ProductHandler $handler): void
    {
        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('product');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        }

        try {
            $productVariantPriceData = $handler->getCollectionData('productVariantPrice');
            $productVariantPriceIds = array_unique(array_filter(array_column($productVariantPriceData, 'pkValue')));
            if (!empty($productVariantPriceIds)) {
                $this->getRepository()->deleteAll(ProductVariantPrice::SOURCE, ProductVariantPrice::PRIMARY_KEY, $productVariantPriceIds);
            }

            $productImageData = $handler->getCollectionData('productImage');
            $productImageIds = array_unique(array_filter(array_column($productImageData, 'pkValue')));
            if (!empty($productImageIds)) {
                $this->getRepository()->deleteAll(ProductImage::SOURCE, ProductImage::PRIMARY_KEY, $productImageIds);
            }

            $productVariantData = $handler->getCollectionData('productVariant');
            $productVariantIds = array_unique(array_filter(array_column($productVariantData, 'pkValue')));
            if (!empty($productVariantIds)) {
                $this->getRepository()->deleteAll(ProductVariant::SOURCE, ProductVariant::PRIMARY_KEY, $productVariantIds);
            }

            if ($aggregateId !== null) {
                $this->getRepository()->delete($rootData['table'], $rootData['pkColumn'], $aggregateId);
            }

            if ($ownsTransaction) {
                $writer->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            }
            throw $e;
        }
    }

    /**
     * Gets Repository service (shared via Factory).
     *
     * @return Repository
     * @throws RuntimeException
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
     * Resolves the database connection to a PDO instance.
     *
     * Handles ConnectionPoolInterface (read/write splitting) and plain PDO.
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        $connection = $this->resource()->dbConnection();

        if ($connection instanceof \PDO) {
            return $connection;
        }

        if ($connection instanceof ConnectionPoolInterface) {
            return $connection->getWriter()->pdo();
        }

        throw new \RuntimeException('No database connection configured');
    }
}
