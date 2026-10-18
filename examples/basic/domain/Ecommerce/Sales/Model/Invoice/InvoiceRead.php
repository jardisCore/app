<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Query\Handler\GetInvoiceByIdHandler;
use Ecommerce\Sales\Model\Invoice\Query\Handler\GetInvoiceByIdentifierHandler;
use Ecommerce\Sales\Model\Invoice\Query\Handler\GetInvoiceByIdentifiersHandler;
use Ecommerce\Sales\Model\Invoice\Query\Handler\GetInvoiceByIdsHandler;
use Ecommerce\Sales\Model\Invoice\Query\Handler\GetInvoiceListHandler;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceListFilter;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceById as QueryInvoiceById;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIdentifier as QueryInvoiceByIdentifier;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIdentifiers as QueryInvoiceByIdentifiers;
use Ecommerce\Sales\Model\Invoice\Query\InvoiceByIds as QueryInvoiceByIds;

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
     * Gets InvoiceByIdentifiers aggregate.
     *
     * @param QueryInvoiceByIdentifiers $invoiceByIdentifiers Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getInvoiceByIdentifiers(
        QueryInvoiceByIdentifiers $invoiceByIdentifiers,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetInvoiceByIdentifiersHandler::class, $invoiceByIdentifiers, $version)();
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
     * Declared query `invoiceList` (Queries.json, visibility public).
     *
     * @param InvoiceListFilter $filter Declared query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function invoiceList(InvoiceListFilter $filter, string $version = ''): ReadResult
    {
        return $this->context(GetInvoiceListHandler::class, $filter, $version)();
    }
}
