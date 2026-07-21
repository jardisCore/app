<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceLineListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use RuntimeException;
use Throwable;
use Ecommerce\Sales\Entity\InvoiceLine as InvoiceLineEntity;

/**
 * List query for InvoiceLineList.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryInvoiceLineList extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(InvoiceLineListFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(InvoiceLineEntity::SOURCE, 'invoiceLine')
            ->select('invoiceLine.id AS id, invoiceLine.identifier AS identifier, invoiceLine.position AS position, invoiceLine.description AS description, invoiceLine.quantity AS quantity, invoiceLine.unit AS unit, invoiceLine.unit_price AS unitPrice, invoiceLine.discount_percent AS discountPercent, invoiceLine.line_total AS lineTotal, invoiceLine.tax_included AS taxIncluded, invoice.invoice_number AS invoiceNumber, COUNT(*) OVER() AS total_count')
            ->leftJoin('invoices', 'invoice.id = invoiceLine.invoice_id', 'invoice');

        $query = $query->where('invoice.invoice_number')->equals($filter->invoiceNumber);

        $query = $query
            ->orderBy('invoiceLine.position', 'ASC')
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
