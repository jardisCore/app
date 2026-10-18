<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Repository;

use Ecommerce\Catalog\Model\Category\Aggregate\Category as CategoryHandler;
use Ecommerce\Catalog\Model\Category\Aggregate\Entity\CategoryTranslation;
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
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(CategoryHandler $handler): ContextResponseInterface
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
            $writer->savepoint('jardis_persist_category');
        }

        $isNew = false;
        try {
            $isNew = $this->persistCategory($handler);
            $this->persistCategoryTranslation($handler);

            $this->deleteRemovedEntities($handler);

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_category');
            }

            return $this->result();
        } catch (UniqueViolationException $violation) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_category');
                $writer->releaseSavepoint('jardis_persist_category');
            }
            throw new ConcurrencyConflictException(
                'duplicate',
                'Category',
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
                    $writer->rollbackToSavepoint('jardis_persist_category');
                    $writer->releaseSavepoint('jardis_persist_category');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_write',
                'Category',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_category');
                $writer->releaseSavepoint('jardis_persist_category');
            }
            throw $e;
        }
    }

    /**
     * Persists category entity data.
     *
     * @param CategoryHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
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
            if (
                !$this->getRepository()->update(
                    $categoryData['table'],
                    $categoryData['pkColumn'],
                    $categoryData['pkValue'],
                    $this->normalizeBoolValues($categoryData['values']),
                    $this->normalizeBoolValues($categoryData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Category',
                    $this->conflictContext($handler)
                );
            }
        }

        return $categoryData['isNew'];
    }

    /**
     * Persists categoryTranslation collection data.
     *
     * @param CategoryHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
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
                if (
                    !$this->getRepository()->update(
                        $categoryTranslationData['table'],
                        $categoryTranslationData['pkColumn'],
                        $categoryTranslationData['pkValue'],
                        $this->normalizeBoolValues($categoryTranslationData['values']),
                        $this->normalizeBoolValues($categoryTranslationData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Category',
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
     * @param CategoryHandler $handler Aggregate handler
     * @throws \RuntimeException
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
     * @throws ConcurrencyConflictException
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     */
    protected function deleteAggregate(CategoryHandler $handler): void
    {
        $writer = $this->resolveWriter();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('category');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        } else {
            $writer->savepoint('jardis_persist_category');
        }

        try {
            $categoryTranslationData = $handler->getCollectionData('categoryTranslation');
            $categoryTranslationIds = array_unique(array_filter(array_column($categoryTranslationData, 'pkValue')));
            if (!empty($categoryTranslationIds)) {
                $this->getRepository()->deleteAll(
                    CategoryTranslation::SOURCE,
                    CategoryTranslation::PRIMARY_KEY,
                    $categoryTranslationIds
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
                        'Category',
                        $this->conflictContext($handler)
                    );
                }
            }

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_category');
            }
        } catch (TransientConflictException $transient) {
            if ($ownsTransaction) {
                if ($writer->inTransaction()) {
                    $writer->rollback();
                }
            } else {
                try {
                    $writer->rollbackToSavepoint('jardis_persist_category');
                    $writer->releaseSavepoint('jardis_persist_category');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_delete',
                'Category',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_category');
                $writer->releaseSavepoint('jardis_persist_category');
            }
            throw $e;
        }
    }

    /**
     * Context of every 409 of this aggregate: the outer-door identifier of the
     * root (`id` only without a public unique key), never FK or child internals.
     *
     * @param CategoryHandler $handler Aggregate handler
     * @return array<string, mixed>
     */
    private function conflictContext(CategoryHandler $handler): array
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
            'categories' => [
                'description' => 'description',
                'identifier' => 'identifier',
                'is_active' => 'isActive',
                'is_visible' => 'isVisible',
                'meta_description' => 'metaDescription',
                'meta_title' => 'metaTitle',
                'name' => 'categoryName',
                'parent_identifier' => 'parentIdentifier',
                'product_count' => 'productCount',
                'slug' => 'slug',
                'sort_order' => 'sortOrder',
            ],
            'category_translations' => [
                'description' => 'categoryTranslation.description',
                'locale' => 'categoryTranslation.locale',
                'meta_description' => 'categoryTranslation.metaDescription',
                'meta_title' => 'categoryTranslation.metaTitle',
                'title' => 'categoryTranslation.title',
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
