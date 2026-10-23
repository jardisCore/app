<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\ItemDiscount;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\OrderItem;
use Ecommerce\Sales\Model\Order\Aggregate\Order as OrderHandler;
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
 * Persists Order with all dependencies.
 *
 * Uses Repository for raw data CRUD operations.
 */
class PersistOrder extends EcommerceContext
{
    private ?RunInTransaction $transactionBracket = null;
    private ?ResolveConnection $connectionResolver = null;

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
     * @throws \RuntimeException
     * @throws Throwable
     * @throws TransactionLostException
     * @throws TransientConflictException
     * @throws UniqueViolationException
     */
    public function __invoke(OrderHandler $handler): ContextResponseInterface
    {
        if ($handler->isMarkedForDeletion()) {
            $this->deleteAggregate($handler);
            return $this->result();
        }

        $write = function () use ($handler): void {
            $this->persistAddress($handler);
            $this->persistCustomer($handler);
            $this->persistOrder($handler);
            $this->persistOrderItem($handler);
            $this->persistItemDiscount($handler);

            $this->deleteRemovedEntities($handler);
        };

        try {
            ($this->transaction())(
                $this->writer(),
                'jardis_persist_order',
                $write,
                nested: false
            );

            return $this->result();
        } catch (TransactionLostException $lost) {
            throw $lost;
        } catch (UniqueViolationException $violation) {
            throw new ConcurrencyConflictException(
                'duplicate',
                'Order',
                $this->conflictContext($handler),
                $this->violatedFields($violation)
            );
        } catch (TransientConflictException $transient) {
            throw new ConcurrencyConflictException(
                'stale_write',
                'Order',
                $this->conflictContext($handler)
            );
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
     * @throws \RuntimeException
     * @throws Throwable
     * @throws TransactionLostException
     * @throws TransientConflictException
     */
    protected function deleteAggregate(OrderHandler $handler): void
    {
        $rootData = $handler->getEntityData('order');
        $aggregateId = $rootData['pkValue'];

        $write = function () use ($handler, $rootData, $aggregateId): void {
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
        };

        try {
            ($this->transaction())(
                $this->writer(),
                'jardis_persist_order',
                $write,
                nested: false
            );
        } catch (TransactionLostException $lost) {
            throw $lost;
        } catch (TransientConflictException $transient) {
            throw new ConcurrencyConflictException(
                'stale_delete',
                'Order',
                $this->conflictContext($handler)
            );
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
