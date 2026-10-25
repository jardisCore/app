<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\Contract\DbQuery\DbQueryBuilderInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Sales\Entity\Voucher as VoucherEntity;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as VoucherByVoucherCodeQuery;

/**
 * Root query class for Voucher (ByVoucherCode variant).
 *
 * Loads the root entity. Follow-up queries are in QueryVoucherAdditions.
 * Generated code - do not modify directly.
 */
class QueryVoucherByVoucherCode extends EcommerceContext
{
    protected array $container = [];

    /**
     * Load root entity for aggregate.
     *
     * @param VoucherByVoucherCodeQuery $query Query parameters
     * @return array<string, array<int, object>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(VoucherByVoucherCodeQuery $query): array
    {
        $this->container['voucher'] = $this->load(
            $this->voucher($query->tenant, $query->voucherCode)
        );

        return $this->container;
    }

    /**
     * Query for voucher.
     * @throws Throwable
     */
    protected function voucher(string $tenant, string $voucherCode): DbQueryBuilderInterface
    {
        return $this->query(VoucherEntity::class)
            ->where('scope_key')->equals($tenant)
            ->and('voucher_code')->equals($voucherCode);
    }

    /**
     * Build a new query for an entity class.
     *
     * @param string $entityClass Entity class name
     * @param string|null $alias Optional table alias
     * @return DbQueryBuilderInterface
     * @throws Throwable
     */
    protected function query(
        string $entityClass,
        ?string $alias = null
    ): DbQueryBuilderInterface {
        return $this->handle(DbQuery::class)
            ->from($entityClass::SOURCE, $alias);
    }

    /**
     * Execute query and return results.
     *
     * @param DbQueryBuilderInterface|null $query Query to execute
     * @return array The query results
     * @throws \RuntimeException
     * @throws Throwable
     */
    protected function load(?DbQueryBuilderInterface $query): array
    {
        if (!$query instanceof DbQueryBuilderInterface) {
            return [];
        }

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
