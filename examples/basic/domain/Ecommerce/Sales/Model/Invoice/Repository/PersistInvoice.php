<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Aggregate\Entity\InvoiceLine;
use Ecommerce\Sales\Model\Invoice\Aggregate\Invoice as InvoiceHandler;
use Exception;
use JardisSupport\Contract\DbConnection\DbConnectionInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Contract\Repository\Exception\TransientConflictException;
use JardisSupport\Contract\Repository\Exception\UniqueViolationException;
use JardisSupport\Repository\Exception\TransactionLostException;
use JardisSupport\Repository\Handler\Transaction\ResolveConnection;
use JardisSupport\Repository\Handler\Transaction\RunInTransaction;
use JardisSupport\Repository\Repository;
use Throwable;

/**
 * Persists Invoice with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistInvoice extends EcommerceContext
{
    private ?RunInTransaction $transactionBracket = null;
    private ?ResolveConnection $connectionResolver = null;

    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param InvoiceHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws \RuntimeException
     * @throws Throwable
     * @throws TransactionLostException
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(InvoiceHandler $handler): ContextResponseInterface
    {
        if ($handler->isMarkedForDeletion()) {
            $this->deleteAggregate($handler);
            return $this->result();
        }

        $write = function () use ($handler): void {
            $this->persistInvoice($handler);
            $this->persistInvoiceLine($handler);

            $this->deleteRemovedEntities($handler);
        };

        try {
            ($this->transaction())(
                $this->writer(),
                'jardis_persist_invoice',
                $write,
                nested: false
            );

            return $this->result();
        } catch (TransactionLostException $lost) {
            throw $lost;
        } catch (UniqueViolationException $violation) {
            throw new ConcurrencyConflictException(
                'duplicate',
                'Invoice',
                $this->conflictContext($handler),
                $this->violatedFields($violation)
            );
        } catch (TransientConflictException $transient) {
            throw new ConcurrencyConflictException(
                'stale_write',
                'Invoice',
                $this->conflictContext($handler)
            );
        }
    }

    /**
     * Persists invoice entity data.
     *
     * @param InvoiceHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
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
            if (
                !$this->getRepository()->update(
                    $invoiceData['table'],
                    $invoiceData['pkColumn'],
                    $invoiceData['pkValue'],
                    $this->normalizeBoolValues($invoiceData['values']),
                    $this->normalizeBoolValues($invoiceData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Invoice',
                    $this->conflictContext($handler)
                );
            }
        }

        return $invoiceData['isNew'];
    }

    /**
     * Persists invoiceLine collection data.
     *
     * @param InvoiceHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
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
                if (
                    !$this->getRepository()->update(
                        $invoiceLineData['table'],
                        $invoiceLineData['pkColumn'],
                        $invoiceLineData['pkValue'],
                        $this->normalizeBoolValues($invoiceLineData['values']),
                        $this->normalizeBoolValues($invoiceLineData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Invoice',
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
     * @param InvoiceHandler $handler Aggregate handler
     * @throws \RuntimeException
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
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     * @throws TransactionLostException
     * @throws TransientConflictException
     */
    protected function deleteAggregate(InvoiceHandler $handler): void
    {
        $rootData = $handler->getEntityData('invoice');
        $aggregateId = $rootData['pkValue'];

        $write = function () use ($handler, $rootData, $aggregateId): void {
            $invoiceLineData = $handler->getCollectionData('invoiceLine');
            $invoiceLineIds = array_unique(array_filter(array_column($invoiceLineData, 'pkValue')));
            if (!empty($invoiceLineIds)) {
                $this->getRepository()->deleteAll(InvoiceLine::SOURCE, InvoiceLine::PRIMARY_KEY, $invoiceLineIds);
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
                        'Invoice',
                        $this->conflictContext($handler)
                    );
                }
            }
        };

        try {
            ($this->transaction())(
                $this->writer(),
                'jardis_persist_invoice',
                $write,
                nested: false
            );
        } catch (TransactionLostException $lost) {
            throw $lost;
        } catch (TransientConflictException $transient) {
            throw new ConcurrencyConflictException(
                'stale_delete',
                'Invoice',
                $this->conflictContext($handler)
            );
        }
    }

    /**
     * Context of every 409 of this aggregate: the outer-door identifier of the
     * root (`id` only without a public unique key), never FK or child internals.
     *
     * @param InvoiceHandler $handler Aggregate handler
     * @return array<string, mixed>
     */
    private function conflictContext(InvoiceHandler $handler): array
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
            'invoice_lines' => [
                'description' => 'invoiceLine.description',
                'discount_percent' => 'invoiceLine.discountPercent',
                'line_total' => 'invoiceLine.lineTotal',
                'position' => 'invoiceLine.position',
                'quantity' => 'invoiceLine.quantity',
                'tax_included' => 'invoiceLine.taxIncluded',
                'unit' => 'invoiceLine.unit',
                'unit_price' => 'invoiceLine.unitPrice',
            ],
            'invoices' => [
                'currency' => 'currency',
                'customer_identifier' => 'customerIdentifier',
                'due_at' => 'dueAt',
                'identifier' => 'identifier',
                'invoice_number' => 'invoiceNumber',
                'issued_at' => 'issuedAt',
                'note' => 'note',
                'order_number' => 'orderNumber',
                'paid_at' => 'paidAt',
                'payment_method' => 'paymentMethod',
                'status' => 'status',
                'tax_rate' => 'taxRate',
                'total_gross' => 'totalGross',
                'total_net' => 'totalNet',
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
     * The transaction bracket of the repository package (bound once per instance).
     */
    protected function transaction(): RunInTransaction
    {
        return $this->transactionBracket ??= new RunInTransaction();
    }

    /**
     * Resolves the write connection from what the kernel holds: the writer of a
     * connection pool, a plain PDO wrapped, null without a database (the bracket
     * answers that with a configuration error).
     */
    protected function writer(): ?DbConnectionInterface
    {
        $this->connectionResolver ??= new ResolveConnection();

        return ($this->connectionResolver)($this->resource()->dbConnection());
    }

    /**
     * Resolves the database connection to a PDO instance (the writer's own).
     *
     * @throws \RuntimeException If no database connection is configured
     */
    protected function resolveConnection(): \PDO
    {
        $writer = $this->writer();
        if ($writer === null) {
            throw new \RuntimeException('No database connection configured');
        }

        return $writer->pdo();
    }
}
