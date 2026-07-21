<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Query\OrderItemListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Entity\OrderItem as OrderItemEntity;

/**
 * List query for OrderItemList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryOrderItemList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(OrderItemListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(OrderItemEntity::SOURCE, 'orderItem')
            ->select('orderItem.id AS id, orderItem.identifier AS identifier, orderItem.quantity AS quantity, orderItem.unit_price AS unitPrice, orderItem.subtotal AS subtotal, order.order_number AS orderNumber, COUNT(*) OVER() AS total_count')
            ->leftJoin('orders', 'order.id = orderItem.order_id', 'order');

        $query = $query->where('order.order_number')->equals($filter->orderNumber);

        $query = $query
            ->orderBy('orderItem.id', 'ASC')
            ->limit($filter->limit, $filter->offset);

        return $this->handle(Repository::class, $this->resolveConnection())->findByQuery($query);
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
            return $connection->getReader()->pdo();
        }

        throw new \RuntimeException('No database connection configured');
    }
}
