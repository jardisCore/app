<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Query\Handler\GetInvoiceByIdHandler;
use Ecommerce\Sales\Aggregate\Invoice\Query\Handler\GetInvoiceByIdentifierHandler;
use Ecommerce\Sales\Aggregate\Invoice\Query\Handler\GetInvoiceByIdsHandler;
use Ecommerce\Sales\Aggregate\Invoice\Query\Handler\GetInvoiceLineListHandler;
use Ecommerce\Sales\Aggregate\Invoice\Query\Handler\GetInvoiceListHandler;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceLineListFilter;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceListFilter;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceById as QueryInvoiceById;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIdentifier as QueryInvoiceByIdentifier;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceByIds as QueryInvoiceByIds;

/**
 * Invoice Aggregate Read Facade.
 *
 * Thin read-only delegator (G9) — hosts the inline query/list
 * operations for this aggregate. No write access; see the sibling
 * write facade in the same directory for commands + event().
 */
class InvoiceRead extends EcommerceContext
{
    /**
     * Gets InvoiceById aggregate.
     *
     * @param QueryInvoiceById $invoiceById Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getInvoiceById(QueryInvoiceById $invoiceById, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetInvoiceByIdHandler::class, $invoiceById, $version)();
    }

    /**
     * Gets InvoiceByIdentifier aggregate.
     *
     * @param QueryInvoiceByIdentifier $invoiceByIdentifier Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getInvoiceByIdentifier(
        QueryInvoiceByIdentifier $invoiceByIdentifier,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetInvoiceByIdentifierHandler::class, $invoiceByIdentifier, $version)();
    }

    /**
     * Gets InvoiceByIds aggregate.
     *
     * @param QueryInvoiceByIds $invoiceByIds Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getInvoiceByIds(QueryInvoiceByIds $invoiceByIds, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetInvoiceByIdsHandler::class, $invoiceByIds, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function invoiceLineList(InvoiceLineListFilter $filter, string $version = ''): array
    {
        return $this->context(GetInvoiceLineListHandler::class, $filter, $version)();
    }

    /**
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function invoiceList(InvoiceListFilter $filter, string $version = ''): array
    {
        return $this->context(GetInvoiceListHandler::class, $filter, $version)();
    }
}
