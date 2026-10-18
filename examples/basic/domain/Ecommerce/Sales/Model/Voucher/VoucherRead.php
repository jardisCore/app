<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Query\Handler\GetVoucherByIdHandler;
use Ecommerce\Sales\Model\Voucher\Query\Handler\GetVoucherByIdsHandler;
use Ecommerce\Sales\Model\Voucher\Query\Handler\GetVoucherByVoucherCodeHandler;
use Ecommerce\Sales\Model\Voucher\Query\Handler\GetVoucherByVoucherCodesHandler;
use Ecommerce\Sales\Model\Voucher\Query\Handler\GetVoucherListHandler;
use Ecommerce\Sales\Model\Voucher\Query\VoucherListFilter;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Query\VoucherById as QueryVoucherById;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByIds as QueryVoucherByIds;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCode as QueryVoucherByVoucherCode;
use Ecommerce\Sales\Model\Voucher\Query\VoucherByVoucherCodes as QueryVoucherByVoucherCodes;

/**
 * Voucher Aggregate Read Facade.
 *
 * Thin read-only delegator (G9) — hosts the inline query/list
 * operations for this aggregate. No write access; see the sibling
 * write facade in the same directory for commands + event().
 */
class VoucherRead extends EcommerceContext
{
    /**
     * Gets VoucherById aggregate.
     *
     * @param QueryVoucherById $voucherById Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getVoucherById(QueryVoucherById $voucherById, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetVoucherByIdHandler::class, $voucherById, $version)();
    }

    /**
     * Gets VoucherByIds aggregate.
     *
     * @param QueryVoucherByIds $voucherByIds Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getVoucherByIds(QueryVoucherByIds $voucherByIds, string $version = ''): DomainResponseInterface
    {
        return $this->context(GetVoucherByIdsHandler::class, $voucherByIds, $version)();
    }

    /**
     * Gets VoucherByVoucherCode aggregate.
     *
     * @param QueryVoucherByVoucherCode $voucherByVoucherCode Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getVoucherByVoucherCode(
        QueryVoucherByVoucherCode $voucherByVoucherCode,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetVoucherByVoucherCodeHandler::class, $voucherByVoucherCode, $version)();
    }

    /**
     * Gets VoucherByVoucherCodes aggregate.
     *
     * @param QueryVoucherByVoucherCodes $voucherByVoucherCodes Query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function getVoucherByVoucherCodes(
        QueryVoucherByVoucherCodes $voucherByVoucherCodes,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(GetVoucherByVoucherCodesHandler::class, $voucherByVoucherCodes, $version)();
    }

    /**
     * Declared query `voucherList` (Queries.json, visibility public).
     *
     * @param VoucherListFilter $filter Declared query parameters
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function voucherList(VoucherListFilter $filter, string $version = ''): ReadResult
    {
        return $this->context(GetVoucherListHandler::class, $filter, $version)();
    }
}
