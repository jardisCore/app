<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Query\OrderListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
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
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(OrderListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(OrderEntity::SOURCE, 'order')
            ->select(
                'order.order_number AS orderNumber, order.total_amount AS totalAmount, order.status AS status, '
                . 'order.created_at AS createdAt, COUNT(*) OVER() AS total_count'
            );

        $hasId = $filter->id !== null;
        $hasOrderNumber = $filter->orderNumber !== null;
        $hasCustomerId = $filter->customerId !== null;
        $hasTotalAmount = $filter->totalAmount !== null;
        $hasStatus = $filter->status !== null;
        $hasCreatedAtFrom = $filter->createdAtFrom !== null;
        $hasCreatedAtUntil = $filter->createdAtUntil !== null;

        if ($hasId) {
            $query = $query->and('order.id')->equals($filter->id);
        }
        if ($hasOrderNumber) {
            $query = $query->and('order.order_number')->equals($filter->orderNumber);
        }
        if ($hasCustomerId) {
            $query = $query->and('order.customer_id')->equals($filter->customerId);
        }
        if ($hasTotalAmount) {
            $query = $query->and('order.total_amount')->equals($filter->totalAmount);
        }
        if ($hasStatus) {
            $query = $query->and('order.status')->equals($filter->status);
        }
        if ($hasCreatedAtFrom) {
            $query = $query->and('order.created_at')->greaterEquals($filter->createdAtFrom);
        }
        if ($hasCreatedAtUntil) {
            $query = $query->and('order.created_at')->lowerEquals($filter->createdAtUntil);
        }
        $query = $query->and('order.tenant_key')->equals($filter->tenant);

        $query = $query
            ->orderBy('order.created_at', 'DESC')
            ->orderBy('order.id', 'ASC')
            ->limit(min($filter->limit, 20), $filter->offset);

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
