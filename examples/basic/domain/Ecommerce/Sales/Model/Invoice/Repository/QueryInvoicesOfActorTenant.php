<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Query\InvoicesOfActorTenantFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
use Throwable;
use Ecommerce\Sales\Entity\Invoice as InvoiceEntity;

/**
 * List query for InvoicesOfActorTenant.
 *
 * Flattened read-only projection with SQL JOINs.
 * Generated code - do not modify directly.
 */
class QueryInvoicesOfActorTenant extends EcommerceContext
{
    /**
     * @return array<int, array<string, mixed>>
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(InvoicesOfActorTenantFilter $filter): array
    {
        $query = $this->handle(DbQuery::class)
            ->from(InvoiceEntity::SOURCE, 'invoice')
            ->select(
                'invoice.identifier AS identifier, invoice.invoice_number AS invoiceNumber, '
                . 'invoice.order_number AS orderNumber, invoice.customer_identifier AS customerIdentifier, '
                . 'invoice.status AS status, invoice.payment_method AS paymentMethod, invoice.total_net AS totalNet, '
                . 'invoice.tax_rate AS taxRate, invoice.total_gross AS totalGross, invoice.currency AS currency, '
                . 'invoice.note AS note, invoice.issued_at AS issuedAt, invoice.due_at AS dueAt, '
                . 'invoice.paid_at AS paidAt, invoice.created_at AS createdAt, COUNT(*) OVER() AS total_count'
            );

        $actor = $this->actor();

        $query = $query->and('invoice.invoice_number')->equals($actor->tenant);

        $query = $query
            ->orderBy('invoice.invoice_number', 'ASC')
            ->orderBy('invoice.id', 'ASC')
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
