<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Repository;

use Ecommerce\Catalog\Aggregate\Category\Aggregate\Entity\CategoryTranslation;
use Ecommerce\EcommerceContext;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Repository\Repository;
use PDOException;
use RuntimeException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category as CategoryHandler;

/**
 * Persists Category with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistCategory extends EcommerceContext
{
    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param CategoryHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws Exception
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(CategoryHandler $handler): ContextResponseInterface
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
            $isNew = $this->persistCategory($handler);
            $this->persistCategoryTranslation($handler);

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
     * Persists category entity data.
     *
     * @param CategoryHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistCategory(CategoryHandler $handler): bool
    {
        $categoryData = $handler->getEntityData('category');
        if ($categoryData['isNew']) {
            if (!empty($categoryData['values'])) {
                $newId = $this->getRepository()->insert(
                    $categoryData['table'],
                    $categoryData['pkColumn'],
                    $this->normalizeBoolValues($categoryData['values'])
                );
                ($categoryData['onInserted'])($newId);
            }
        } elseif (!empty($categoryData['values'])) {
            $this->getRepository()->update(
                $categoryData['table'],
                $categoryData['pkColumn'],
                $categoryData['pkValue'],
                $this->normalizeBoolValues($categoryData['values'])
            );
        }

        return $categoryData['isNew'];
    }

    /**
     * Persists categoryTranslation collection data.
     *
     * @param CategoryHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistCategoryTranslation(CategoryHandler $handler): void
    {
        foreach ($handler->getCollectionData('categoryTranslation') as $categoryTranslationData) {
            if ($categoryTranslationData['isNew']) {
                if (!empty($categoryTranslationData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $categoryTranslationData['table'],
                        $categoryTranslationData['pkColumn'],
                        $this->normalizeBoolValues($categoryTranslationData['values'])
                    );
                    ($categoryTranslationData['onInserted'])($newId);
                }
            } elseif (!empty($categoryTranslationData['values'])) {
                $this->getRepository()->update(
                    $categoryTranslationData['table'],
                    $categoryTranslationData['pkColumn'],
                    $categoryTranslationData['pkValue'],
                    $this->normalizeBoolValues($categoryTranslationData['values'])
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
     * @param CategoryHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteRemovedEntities(CategoryHandler $handler): void
    {
        $removedIds = $handler->getRemovedIds();
        if (!empty($removedIds['categoryTranslation'])) {
            $this->getRepository()->deleteAll(
                CategoryTranslation::SOURCE,
                CategoryTranslation::PRIMARY_KEY,
                $removedIds['categoryTranslation']
            );
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param CategoryHandler $handler The aggregate handler
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteAggregate(CategoryHandler $handler): void
    {
        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('category');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        }

        try {
            $categoryTranslationData = $handler->getCollectionData('categoryTranslation');
            $categoryTranslationIds = array_unique(array_filter(array_column($categoryTranslationData, 'pkValue')));
            if (!empty($categoryTranslationIds)) {
                $this->getRepository()->deleteAll(CategoryTranslation::SOURCE, CategoryTranslation::PRIMARY_KEY, $categoryTranslationIds);
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
