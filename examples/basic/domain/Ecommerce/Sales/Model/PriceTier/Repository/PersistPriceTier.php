<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier as PriceTierHandler;
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
 * Persists PriceTier with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistPriceTier extends EcommerceContext
{
    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param PriceTierHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(PriceTierHandler $handler): ContextResponseInterface
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
            $writer->savepoint('jardis_persist_price_tier');
        }

        $isNew = false;
        try {
            $isNew = $this->persistPriceTier($handler);

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_price_tier');
            }

            return $this->result();
        } catch (UniqueViolationException $violation) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_price_tier');
                $writer->releaseSavepoint('jardis_persist_price_tier');
            }
            throw new ConcurrencyConflictException(
                'duplicate',
                'PriceTier',
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
                    $writer->rollbackToSavepoint('jardis_persist_price_tier');
                    $writer->releaseSavepoint('jardis_persist_price_tier');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_write',
                'PriceTier',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_price_tier');
                $writer->releaseSavepoint('jardis_persist_price_tier');
            }
            throw $e;
        }
    }

    /**
     * Persists priceTier entity data.
     *
     * @param PriceTierHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistPriceTier(PriceTierHandler $handler): bool
    {
        $priceTierData = $handler->getEntityData('priceTier');
        if ($priceTierData['isNew']) {
            if (!empty($priceTierData['values'])) {
                $newId = $this->getRepository()->insert(
                    $priceTierData['table'],
                    $priceTierData['pkColumn'],
                    $this->normalizeBoolValues($priceTierData['values'])
                );
                ($priceTierData['onInserted'])($newId);
            }
        } elseif (!empty($priceTierData['values'])) {
            if (
                !$this->getRepository()->update(
                    $priceTierData['table'],
                    $priceTierData['pkColumn'],
                    $priceTierData['pkValue'],
                    $this->normalizeBoolValues($priceTierData['values']),
                    $this->normalizeBoolValues($priceTierData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'PriceTier',
                    $this->conflictContext($handler)
                );
            }
        }

        return $priceTierData['isNew'];
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param PriceTierHandler $handler The aggregate handler
     * @throws ConcurrencyConflictException
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     */
    protected function deleteAggregate(PriceTierHandler $handler): void
    {
        $writer = $this->resolveWriter();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('priceTier');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        } else {
            $writer->savepoint('jardis_persist_price_tier');
        }

        try {
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
                        'PriceTier',
                        $this->conflictContext($handler)
                    );
                }
            }

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_price_tier');
            }
        } catch (TransientConflictException $transient) {
            if ($ownsTransaction) {
                if ($writer->inTransaction()) {
                    $writer->rollback();
                }
            } else {
                try {
                    $writer->rollbackToSavepoint('jardis_persist_price_tier');
                    $writer->releaseSavepoint('jardis_persist_price_tier');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_delete',
                'PriceTier',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_price_tier');
                $writer->releaseSavepoint('jardis_persist_price_tier');
            }
            throw $e;
        }
    }

    /**
     * Context of every 409 of this aggregate: the outer-door identifier of the
     * root (`id` only without a public unique key), never FK or child internals.
     *
     * @param PriceTierHandler $handler Aggregate handler
     * @return array<string, mixed>
     */
    private function conflictContext(PriceTierHandler $handler): array
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
            'price_tiers' => [
                'identifier' => 'identifier',
                'min_quantity' => 'minQuantity',
                'tier_code' => 'tierCode',
                'unit_price' => 'unitPrice',
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
