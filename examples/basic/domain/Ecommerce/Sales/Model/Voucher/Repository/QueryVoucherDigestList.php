<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Query\VoucherDigestListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Sales\Entity\Voucher as VoucherEntity;

/**
 * List query for VoucherDigestList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryVoucherDigestList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(VoucherDigestListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(VoucherEntity::SOURCE, 'voucher')
            ->select(
                'voucher.id AS id, voucher.scope_key AS scopeKey, voucher.voucher_code AS voucherCode, '
                . 'voucher.amount AS amount, voucher.note AS note, voucher.secret_digest AS secretDigest, '
                . 'voucher.issued_by AS issuedBy, voucher.issuer_id AS issuerId, voucher.created_at AS createdAt, '
                . 'COUNT(*) OVER() AS total_count'
            );

        $query = $query->and('voucher.scope_key')->equals($filter->scopeKey);
        $query = $query->and('voucher.voucher_code')->equals($filter->voucherCode);

        $query = $query
            ->orderBy('voucher.voucher_code', 'ASC')
            ->orderBy('voucher.id', 'ASC')
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
