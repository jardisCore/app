<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Entity\Invoice as InvoiceEntity;

/**
 * List query for InvoiceList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryInvoiceList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(InvoiceListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(InvoiceEntity::SOURCE, 'invoice')
            ->select('invoice.id AS id, invoice.identifier AS identifier, invoice.invoice_number AS invoiceNumber, invoice.order_number AS orderNumber, invoice.customer_identifier AS customerIdentifier, invoice.status AS status, invoice.payment_method AS paymentMethod, invoice.total_gross AS totalGross, invoice.currency AS currency, invoice.issued_at AS issuedAt, invoice.due_at AS dueAt, COUNT(*) OVER() AS total_count');

        if ($filter->status !== null) {
            $query = $query->where('invoice.status')->equals($filter->status);
        }
        if ($filter->customerIdentifier !== null) {
            $query = $query->and('invoice.customer_identifier')->equals($filter->customerIdentifier);
        }

        $query = $query
            ->orderBy('invoice.created_at', 'DESC')
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
