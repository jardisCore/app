<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Query\OrdersOfActorFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Sales\Entity\Order as OrderEntity;

/**
 * List query for OrdersOfActor.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryOrdersOfActor extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     * @throws Throwable
     * @throws \UnexpectedValueException
     */
    public function __invoke(OrdersOfActorFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(OrderEntity::SOURCE, 'order')
            ->select(
                'order.order_number AS orderNumber, order.total_amount AS totalAmount, order.status AS status, '
                . 'order.created_at AS createdAt, COUNT(*) OVER() AS total_count'
            );

        $actor = $this->actor();
        $actorId = filter_var($actor->id, FILTER_VALIDATE_INT);
        if ($actorId === false) {
            throw new \UnexpectedValueException('The actor id is not a whole number.');
        }

        $query = $query->and('order.customer_id')->equals($actorId);

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
