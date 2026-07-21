<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Query\OrderListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Entity\Order as OrderEntity;

/**
 * List query for OrderList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryOrderList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(OrderListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(OrderEntity::SOURCE, 'order')
            ->select('order.id AS id, order.order_number AS orderNumber, order.total_amount AS totalAmount, order.status AS status, order.created_at AS createdAt, customer.name AS customerName, customer.email AS email, COUNT(*) OVER() AS total_count')
            ->leftJoin('customers', 'customer.id = order.customer_id', 'customer');

        if ($filter->status !== null) {
            $query = $query->where('order.status')->equals($filter->status);
        }
        if ($filter->search !== null) {
            $query = $query->and('customer.name')->like($filter->search);
        }

        $query = $query
            ->orderBy('order.created_at', 'DESC')
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
