<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Query\VoucherChannelKeyListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Sales\Entity\VoucherChannel as VoucherChannelEntity;

/**
 * List query for VoucherChannelKeyList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryVoucherChannelKeyList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(VoucherChannelKeyListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(VoucherChannelEntity::SOURCE, 'voucherChannel')
            ->select(
                'voucherChannel.id AS id, voucherChannel.identifier AS identifier, voucherChannel.label AS label, '
                . 'voucherChannel.access_key AS accessKey, COUNT(*) OVER() AS total_count'
            )
            ->innerJoin('vouchers', 'voucherChannel.voucher_id = voucher.id', 'voucher')
            ->groupBy('voucherChannel.id');

        $query = $query->and('voucher.scope_key')->equals($filter->scopeKey);
        $query = $query->and('voucherChannel.label')->equals($filter->label);

        $query = $query
            ->orderBy('voucherChannel.label', 'ASC')
            ->orderBy('voucherChannel.id', 'ASC')
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
