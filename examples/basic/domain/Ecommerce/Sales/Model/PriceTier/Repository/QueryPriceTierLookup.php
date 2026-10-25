<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Query\PriceTierLookupFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Sales\Entity\PriceTier as PriceTierEntity;

/**
 * List query for PriceTierLookup.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryPriceTierLookup extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(PriceTierLookupFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(PriceTierEntity::SOURCE, 'priceTier')
            ->select(
                'priceTier.id AS id, priceTier.tenant_key AS tenantKey, priceTier.identifier AS identifier, '
                . 'priceTier.tier_code AS tierCode, priceTier.min_quantity AS minQuantity, '
                . 'priceTier.unit_price AS unitPrice, priceTier.created_at AS createdAt, COUNT(*) OVER() AS total_count'
            );

        $query = $query->and('priceTier.tier_code')->equals($filter->tierCode);
        $query = $query->and('priceTier.tenant_key')->equals($filter->tenant);

        $query = $query
            ->orderBy('priceTier.min_quantity', 'ASC')
            ->orderBy('priceTier.id', 'ASC')
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
