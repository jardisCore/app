<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Address;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Customer;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\ItemDiscount;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\OrderItem;
use Exception;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\Kernel\ContextResponseInterface;
use JardisSupport\Repository\Repository;
use PDOException;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order as OrderHandler;

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
     * @throws Exception
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(OrderHandler $handler): ContextResponseInterface
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
            $this->persistAddress($handler);
            $this->persistCustomer($handler);
            $isNew = $this->persistOrder($handler);
            $this->persistOrderItem($handler);
            $this->persistItemDiscount($handler);

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
     * Persists address entity data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws RuntimeException
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
            $this->getRepository()->update(
                $addressData['table'],
                $addressData['pkColumn'],
                $addressData['pkValue'],
                $this->normalizeBoolValues($addressData['values'])
            );
        }
    }

    /**
     * Persists customer entity data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws RuntimeException
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
            $this->getRepository()->update(
                $customerData['table'],
                $customerData['pkColumn'],
                $customerData['pkValue'],
                $this->normalizeBoolValues($customerData['values'])
            );
        }
    }

    /**
     * Persists order entity data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @return bool Whether the entity is new
     * @throws RuntimeException
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
            $this->getRepository()->update(
                $orderData['table'],
                $orderData['pkColumn'],
                $orderData['pkValue'],
                $this->normalizeBoolValues($orderData['values'])
            );
        }

        return $orderData['isNew'];
    }

    /**
     * Persists orderItem collection data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws RuntimeException
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
                $this->getRepository()->update(
                    $orderItemData['table'],
                    $orderItemData['pkColumn'],
                    $orderItemData['pkValue'],
                    $this->normalizeBoolValues($orderItemData['values'])
                );
            }
        }
    }

    /**
     * Persists itemDiscount collection data.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws RuntimeException
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
                $this->getRepository()->update(
                    $itemDiscountData['table'],
                    $itemDiscountData['pkColumn'],
                    $itemDiscountData['pkValue'],
                    $this->normalizeBoolValues($itemDiscountData['values'])
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
     * @param OrderHandler $handler Aggregate handler
     * @throws RuntimeException
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

        if (!empty($removedIds['customer'])) {
            $this->getRepository()->deleteAll(Customer::SOURCE, Customer::PRIMARY_KEY, $removedIds['customer']);
        }

        if (!empty($removedIds['address'])) {
            $this->getRepository()->deleteAll(Address::SOURCE, Address::PRIMARY_KEY, $removedIds['address']);
        }
    }

    /**
     * Deletes the entire aggregate from the database.
     *
     * Deletes all entities in FK-safe order.
     *
     * @param OrderHandler $handler The aggregate handler
     * @throws PDOException
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function deleteAggregate(OrderHandler $handler): void
    {
        $writer = $this->resolveConnection();
        $ownsTransaction = !$writer->inTransaction();

        $rootData = $handler->getEntityData('order');
        $aggregateId = $rootData['pkValue'];

        if ($ownsTransaction) {
            $writer->beginTransaction();
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
                $this->getRepository()->delete($rootData['table'], $rootData['pkColumn'], $aggregateId);
            }

            $customerData = $handler->getEntityData('customer');
            if ($customerData['pkValue'] !== null) {
                $this->getRepository()->delete(Customer::SOURCE, Customer::PRIMARY_KEY, $customerData['pkValue']);
            }

            $addressData = $handler->getEntityData('address');
            if ($addressData['pkValue'] !== null) {
                $this->getRepository()->delete(Address::SOURCE, Address::PRIMARY_KEY, $addressData['pkValue']);
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
