<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceListFilter;
use JardisSupport\Contract\DbConnection\ConnectionPoolInterface;
use JardisSupport\DbQuery\DbQuery;
use JardisSupport\Repository\Repository;
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
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(InvoiceListFilter $filter): array
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

        $hasId = $filter->id !== null;
        $hasIdentifier = $filter->identifier !== null;
        $hasInvoiceNumber = $filter->invoiceNumber !== null;
        $hasOrderNumber = $filter->orderNumber !== null;
        $hasCustomerIdentifier = $filter->customerIdentifier !== null;
        $hasStatus = $filter->status !== null;
        $hasPaymentMethod = $filter->paymentMethod !== null;
        $hasTotalNet = $filter->totalNet !== null;
        $hasTaxRate = $filter->taxRate !== null;
        $hasTotalGross = $filter->totalGross !== null;
        $hasCurrency = $filter->currency !== null;
        $hasNote = $filter->note !== null;
        $hasIssuedAtFrom = $filter->issuedAtFrom !== null;
        $hasIssuedAtUntil = $filter->issuedAtUntil !== null;
        $hasDueAtFrom = $filter->dueAtFrom !== null;
        $hasDueAtUntil = $filter->dueAtUntil !== null;
        $hasPaidAtFrom = $filter->paidAtFrom !== null;
        $hasPaidAtUntil = $filter->paidAtUntil !== null;
        $hasCreatedAtFrom = $filter->createdAtFrom !== null;
        $hasCreatedAtUntil = $filter->createdAtUntil !== null;

        if ($hasId) {
            $query = $query->and('invoice.id')->equals($filter->id);
        }
        if ($hasIdentifier) {
            $query = $query->and('invoice.identifier')->equals($filter->identifier);
        }
        if ($hasInvoiceNumber) {
            $query = $query->and('invoice.invoice_number')->equals($filter->invoiceNumber);
        }
        if ($hasOrderNumber) {
            $query = $query->and('invoice.order_number')->equals($filter->orderNumber);
        }
        if ($hasCustomerIdentifier) {
            $query = $query->and('invoice.customer_identifier')->equals($filter->customerIdentifier);
        }
        if ($hasStatus) {
            $query = $query->and('invoice.status')->equals($filter->status);
        }
        if ($hasPaymentMethod) {
            $query = $query->and('invoice.payment_method')->equals($filter->paymentMethod);
        }
        if ($hasTotalNet) {
            $query = $query->and('invoice.total_net')->equals($filter->totalNet);
        }
        if ($hasTaxRate) {
            $query = $query->and('invoice.tax_rate')->equals($filter->taxRate);
        }
        if ($hasTotalGross) {
            $query = $query->and('invoice.total_gross')->equals($filter->totalGross);
        }
        if ($hasCurrency) {
            $query = $query->and('invoice.currency')->equals($filter->currency);
        }
        if ($hasNote) {
            $query = $query->and('invoice.note')->equals($filter->note);
        }
        if ($hasIssuedAtFrom) {
            $query = $query->and('invoice.issued_at')->greaterEquals($filter->issuedAtFrom);
        }
        if ($hasIssuedAtUntil) {
            $query = $query->and('invoice.issued_at')->lowerEquals($filter->issuedAtUntil);
        }
        if ($hasDueAtFrom) {
            $query = $query->and('invoice.due_at')->greaterEquals($filter->dueAtFrom);
        }
        if ($hasDueAtUntil) {
            $query = $query->and('invoice.due_at')->lowerEquals($filter->dueAtUntil);
        }
        if ($hasPaidAtFrom) {
            $query = $query->and('invoice.paid_at')->greaterEquals($filter->paidAtFrom);
        }
        if ($hasPaidAtUntil) {
            $query = $query->and('invoice.paid_at')->lowerEquals($filter->paidAtUntil);
        }
        if ($hasCreatedAtFrom) {
            $query = $query->and('invoice.created_at')->greaterEquals($filter->createdAtFrom);
        }
        if ($hasCreatedAtUntil) {
            $query = $query->and('invoice.created_at')->lowerEquals($filter->createdAtUntil);
        }

        $query = $query
            ->orderBy('invoice.issued_at', 'DESC')
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
