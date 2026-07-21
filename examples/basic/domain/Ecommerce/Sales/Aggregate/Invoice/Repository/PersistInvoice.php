<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity\InvoiceLine;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Repository\Repository;
use PDOException;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice as InvoiceHandler;

/**
 * Persists Invoice with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistInvoice extends EcommerceContext
{
    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param InvoiceHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws Exception
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(InvoiceHandler $handler): ContextResponseInterface
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
            $isNew = $this->persistInvoice($handler);
            $this->persistInvoiceLine($handler);

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
     * Persists invoice entity data.
     *
     * @param InvoiceHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistInvoice(InvoiceHandler $handler): bool
    {
        $invoiceData = $handler->getEntityData('invoice');
        if ($invoiceData['isNew']) {
            if (!empty($invoiceData['values'])) {
                $newId = $this->getRepository()->insert(
                    $invoiceData['table'],
                    $invoiceData['pkColumn'],
                    $this->normalizeBoolValues($invoiceData['values'])
                );
                ($invoiceData['onInserted'])($newId);
            }
        } elseif (!empty($invoiceData['values'])) {
            $this->getRepository()->update(
                $invoiceData['table'],
                $invoiceData['pkColumn'],
                $invoiceData['pkValue'],
                $this->normalizeBoolValues($invoiceData['values'])
            );
        }

        return $invoiceData['isNew'];
    }

    /**
     * Persists invoiceLine collection data.
     *
     * @param InvoiceHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function persistInvoiceLine(InvoiceHandler $handler): void
    {
        foreach ($handler->getCollectionData('invoiceLine') as $invoiceLineData) {
            if ($invoiceLineData['isNew']) {
                if (!empty($invoiceLineData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $invoiceLineData['table'],
                        $invoiceLineData['pkColumn'],
                        $this->normalizeBoolValues($invoiceLineData['values'])
                    );
                    ($invoiceLineData['onInserted'])($newId);
                }
            } elseif (!empty($invoiceLineData['values'])) {
                $this->getRepository()->update(
                    $invoiceLineData['table'],
                    $invoiceLineData['pkColumn'],
                    $invoiceLineData['pkValue'],
                    $this->normalizeBoolValues($invoiceLineData['values'])
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
     * @param InvoiceHandler $handler Aggregate handler
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteRemovedEntities(InvoiceHandler $handler): void
    {
        $removedIds = $handler->getRemovedIds();
        if (!empty($removedIds['invoiceLine'])) {
            $this->getRepository()->deleteAll(
                InvoiceLine::SOURCE,
                InvoiceLine::PRIMARY_KEY,
                $removedIds['invoiceLine']
            );
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param InvoiceHandler $handler The aggregate handler
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteAggregate(InvoiceHandler $handler): void
    {
        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('invoice');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        }

        try {
            $invoiceLineData = $handler->getCollectionData('invoiceLine');
            $invoiceLineIds = array_unique(array_filter(array_column($invoiceLineData, 'pkValue')));
            if (!empty($invoiceLineIds)) {
                $this->getRepository()->deleteAll(InvoiceLine::SOURCE, InvoiceLine::PRIMARY_KEY, $invoiceLineIds);
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
