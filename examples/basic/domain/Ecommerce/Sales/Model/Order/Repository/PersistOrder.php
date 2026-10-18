<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\ItemDiscount;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\OrderItem;
use Ecommerce\Sales\Model\Order\Aggregate\Order as OrderHandler;
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
 * Persists Order with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistOrder extends EcommerceContext
{
    /**
     * Persists the complete aggregate with transaction management.
     *
     * Uses AggregateHandler public API for data access (no Reflection).
     * Uses Repository for raw data CRUD operations.
     *
     * @param OrderHandler $handler Aggregate handler with business logic
     * @return ContextResponseInterface Response with events
     * @throws ConcurrencyConflictException
     * @throws Exception
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(OrderHandler $handler): ContextResponseInterface
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
            $writer->savepoint('jardis_persist_order');
        }

        $isNew = false;
        try {
            $this->persistAddress($handler);
            $this->persistCustomer($handler);
            $isNew = $this->persistOrder($handler);
            $this->persistOrderItem($handler);
            $this->persistItemDiscount($handler);

            $this->deleteRemovedEntities($handler);

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_order');
            }

            return $this->result();
        } catch (UniqueViolationException $violation) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_order');
                $writer->releaseSavepoint('jardis_persist_order');
            }
            throw new ConcurrencyConflictException(
                'duplicate',
                'Order',
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
                    $writer->rollbackToSavepoint('jardis_persist_order');
                    $writer->releaseSavepoint('jardis_persist_order');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_write',
                'Order',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_order');
                $writer->releaseSavepoint('jardis_persist_order');
            }
            throw $e;
        }
    }

    /**
     * Persists address entity data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistAddress(OrderHandler $handler): void
    {
        $addressData = $handler->getEntityData('address');
        if ($addressData['isNew']) {
            if (!empty($addressData['values'])) {
                $newId = $this->getRepository()->insert(
                    $addressData['table'],
                    $addressData['pkColumn'],
                    $this->normalizeBoolValues($addressData['values'])
                );
                ($addressData['onInserted'])($newId);
            }
        } elseif (!empty($addressData['values'])) {
            if (
                !$this->getRepository()->update(
                    $addressData['table'],
                    $addressData['pkColumn'],
                    $addressData['pkValue'],
                    $this->normalizeBoolValues($addressData['values']),
                    $this->normalizeBoolValues($addressData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Order',
                    $this->conflictContext($handler)
                );
            }
        }
    }

    /**
     * Persists customer entity data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistCustomer(OrderHandler $handler): void
    {
        $customerData = $handler->getEntityData('customer');
        if ($customerData['isNew']) {
            if (!empty($customerData['values'])) {
                $newId = $this->getRepository()->insert(
                    $customerData['table'],
                    $customerData['pkColumn'],
                    $this->normalizeBoolValues($customerData['values'])
                );
                ($customerData['onInserted'])($newId);
            }
        } elseif (!empty($customerData['values'])) {
            if (
                !$this->getRepository()->update(
                    $customerData['table'],
                    $customerData['pkColumn'],
                    $customerData['pkValue'],
                    $this->normalizeBoolValues($customerData['values']),
                    $this->normalizeBoolValues($customerData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Order',
                    $this->conflictContext($handler)
                );
            }
        }
    }

    /**
     * Persists order entity data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistOrder(OrderHandler $handler): bool
    {
        $orderData = $handler->getEntityData('order');
        if ($orderData['isNew']) {
            if (!empty($orderData['values'])) {
                $newId = $this->getRepository()->insert(
                    $orderData['table'],
                    $orderData['pkColumn'],
                    $this->normalizeBoolValues($orderData['values'])
                );
                ($orderData['onInserted'])($newId);
            }
        } elseif (!empty($orderData['values'])) {
            if (
                !$this->getRepository()->update(
                    $orderData['table'],
                    $orderData['pkColumn'],
                    $orderData['pkValue'],
                    $this->normalizeBoolValues($orderData['values']),
                    $this->normalizeBoolValues($orderData['expected'])
                )
            ) {
                throw new ConcurrencyConflictException(
                    'stale_write',
                    'Order',
                    $this->conflictContext($handler)
                );
            }
        }

        return $orderData['isNew'];
    }

    /**
     * Persists orderItem collection data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistOrderItem(OrderHandler $handler): void
    {
        foreach ($handler->getCollectionData('orderItem') as $orderItemData) {
            if ($orderItemData['isNew']) {
                if (!empty($orderItemData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $orderItemData['table'],
                        $orderItemData['pkColumn'],
                        $this->normalizeBoolValues($orderItemData['values'])
                    );
                    ($orderItemData['onInserted'])($newId);
                }
            } elseif (!empty($orderItemData['values'])) {
                if (
                    !$this->getRepository()->update(
                        $orderItemData['table'],
                        $orderItemData['pkColumn'],
                        $orderItemData['pkValue'],
                        $this->normalizeBoolValues($orderItemData['values']),
                        $this->normalizeBoolValues($orderItemData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Order',
                        $this->conflictContext($handler)
                    );
                }
            }
        }
    }

    /**
     * Persists itemDiscount collection data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws ConcurrencyConflictException
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function persistItemDiscount(OrderHandler $handler): void
    {
        foreach ($handler->getCollectionData('itemDiscount') as $itemDiscountData) {
            if ($itemDiscountData['isNew']) {
                if (!empty($itemDiscountData['values'])) {
                    $newId = $this->getRepository()->insert(
                        $itemDiscountData['table'],
                        $itemDiscountData['pkColumn'],
                        $this->normalizeBoolValues($itemDiscountData['values'])
                    );
                    ($itemDiscountData['onInserted'])($newId);
                }
            } elseif (!empty($itemDiscountData['values'])) {
                if (
                    !$this->getRepository()->update(
                        $itemDiscountData['table'],
                        $itemDiscountData['pkColumn'],
                        $itemDiscountData['pkValue'],
                        $this->normalizeBoolValues($itemDiscountData['values']),
                        $this->normalizeBoolValues($itemDiscountData['expected'])
                    )
                ) {
                    throw new ConcurrencyConflictException(
                        'stale_write',
                        'Order',
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
     * @param OrderHandler $handler Aggregate handler
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function deleteRemovedEntities(OrderHandler $handler): void
    {
        $removedIds = $handler->getRemovedIds();
        if (!empty($removedIds['itemDiscount'])) {
            $this->getRepository()->deleteAll(
                ItemDiscount::SOURCE,
                ItemDiscount::PRIMARY_KEY,
                $removedIds['itemDiscount']
            );
        }

        if (!empty($removedIds['orderItem'])) {
            $this->getRepository()->deleteAll(OrderItem::SOURCE, OrderItem::PRIMARY_KEY, $removedIds['orderItem']);
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param OrderHandler $handler The aggregate handler
     * @throws ConcurrencyConflictException
     * @throws PDOException
     * @throws \RuntimeException
     * @throws \Throwable
     * @throws TransientConflictException
     */
    protected function deleteAggregate(OrderHandler $handler): void
    {
        $writer = $this->resolveWriter();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('order');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
        } else {
            $writer->savepoint('jardis_persist_order');
        }

        try {
            $itemDiscountData = $handler->getCollectionData('itemDiscount');
            $itemDiscountIds = array_unique(array_filter(array_column($itemDiscountData, 'pkValue')));
            if (!empty($itemDiscountIds)) {
                $this->getRepository()->deleteAll(ItemDiscount::SOURCE, ItemDiscount::PRIMARY_KEY, $itemDiscountIds);
            }

            $orderItemData = $handler->getCollectionData('orderItem');
            $orderItemIds = array_unique(array_filter(array_column($orderItemData, 'pkValue')));
            if (!empty($orderItemIds)) {
                $this->getRepository()->deleteAll(OrderItem::SOURCE, OrderItem::PRIMARY_KEY, $orderItemIds);
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
                        'Order',
                        $this->conflictContext($handler)
                    );
                }
            }

            if ($ownsTransaction) {
                $writer->commit();
            } else {
                $writer->releaseSavepoint('jardis_persist_order');
            }
        } catch (TransientConflictException $transient) {
            if ($ownsTransaction) {
                if ($writer->inTransaction()) {
                    $writer->rollback();
                }
            } else {
                try {
                    $writer->rollbackToSavepoint('jardis_persist_order');
                    $writer->releaseSavepoint('jardis_persist_order');
                } catch (\RuntimeException) {
                    throw $transient;
                }
            }
            throw new ConcurrencyConflictException(
                'stale_delete',
                'Order',
                $this->conflictContext($handler)
            );
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                $writer->rollback();
            } else {
                $writer->rollbackToSavepoint('jardis_persist_order');
                $writer->releaseSavepoint('jardis_persist_order');
            }
            throw $e;
        }
    }

    /**
     * Context of every 409 of this aggregate: the outer-door identifier of the
     * root (`id` only without a public unique key), never FK or child internals.
     *
     * @param OrderHandler $handler Aggregate handler
     * @return array<string, mixed>
     */
    private function conflictContext(OrderHandler $handler): array
    {
        return ['orderNumber' => $handler->getData()->getOrderNumber()];
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
            'addresses' => [
                'city' => 'address.city',
                'country' => 'address.country',
                'postal_code' => 'address.postalCode',
                'street' => 'address.street',
            ],
            'customers' => [
                'email' => 'customer.email',
                'name' => 'customer.customerName',
                'phone' => 'customer.phone',
            ],
            'item_discounts' => ['amount' => 'itemDiscount.amount', 'discount_code' => 'itemDiscount.discountCode'],
            'order_items' => [
                'product_identifier' => 'orderItem.productIdentifier',
                'quantity' => 'orderItem.quantity',
                'subtotal' => 'orderItem.subtotal',
                'unit_price' => 'orderItem.unitPrice',
            ],
            'orders' => ['order_number' => 'orderNumber', 'status' => 'status', 'total_amount' => 'totalAmount'],
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
