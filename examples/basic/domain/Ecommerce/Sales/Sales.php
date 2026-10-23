<?php

declare(strict_types=1);

namespace Ecommerce\Sales;

use Throwable;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Ecommerce\Sales\Model\Invoice\InvoiceRead;
use Ecommerce\Sales\Model\Order\OrderRead;
use Ecommerce\Sales\Model\Voucher\VoucherRead;
use Ecommerce\Sales\Process\SalesProcess;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder as CommandUpdateOrder;
use Ecommerce\Sales\Model\Order\Command\Handler\UpdateOrder;
use Ecommerce\Sales\Model\Order\Command\UpdateOrderItem as CommandUpdateOrderItem;
use Ecommerce\Sales\Model\Order\Command\Handler\UpdateOrderItem;
use Ecommerce\Sales\Model\Voucher\Command\UpdateVoucher as CommandUpdateVoucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\UpdateVoucher;
use JardisSupport\Response\Handler\Response\ProjectAnswer;
use Ecommerce\EcommerceCheckAccess;
use Ecommerce\EcommerceRights;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Data\Response\ContextResponse;
use JardisSupport\Response\Handler\Response\TransformResponse;

/**
 * Sales Bounded Context.
 *
 * Public API (the "Außentür", G2): one read accessor per aggregate
 * plus process() plus any explicitly exposed rule-guarded command
 * (G10). Aggregate writes beyond an exposed command are not exposed here.
 *
 * Usage:
 *   $bc->invoice()->{useCase}(...)
 *   $bc->order()->{useCase}(...)
 *   $bc->voucher()->{useCase}(...)
 *   $bc->process()->{process}(...)
 *   $bc->updateOrder($updateOrder)
 *   $bc->updateOrderItem($updateOrderItem)
 *   $bc->updateVoucher($updateVoucher)
 */
class Sales extends EcommerceContext
{
    /**
     * Returns the Invoice aggregate read facade.
     *
     * @return InvoiceRead
     * @throws Throwable
     */
    public function invoice(): InvoiceRead
    {
        return $this->handle(InvoiceRead::class);
    }

    /**
     * Returns the Order aggregate read facade.
     *
     * @return OrderRead
     * @throws Throwable
     */
    public function order(): OrderRead
    {
        return $this->handle(OrderRead::class);
    }

    /**
     * Returns the Voucher aggregate read facade.
     *
     * @return VoucherRead
     * @throws Throwable
     */
    public function voucher(): VoucherRead
    {
        return $this->handle(VoucherRead::class);
    }

    /**
     * Returns the process facade bundling this bounded context's processes.
     *
     * @return SalesProcess
     * @throws Throwable
     */
    public function process(): SalesProcess
    {
        return $this->handle(SalesProcess::class);
    }

    /**
     * Executes the rule-guarded UpdateOrder command (G10 Außentür — the Rule chain runs structurally in the dispatch).
     *
     * @param CommandUpdateOrder $updateOrder
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateOrder(CommandUpdateOrder $updateOrder, string $version = ''): DomainResponseInterface
    {
        $denied = ($this->handle(EcommerceCheckAccess::class))(
            $this->entry(),
            $this->actor(),
            EcommerceRights::SALES_ORDER__UPDATEORDER
        );
        if ($denied !== null) {
            return ($this->handle(AnswerThrowable::class))(
                new ContextResponse('UpdateOrder'),
                new TransformResponse($this->clock(), $version !== '' ? $version : $this->version()),
                $denied,
                true,
                'UpdateOrder'
            );
        }

        return ($this->handle(ProjectAnswer::class))(
            $this->context(UpdateOrder::class, $updateOrder, $version)(),
            'orderNumber'
        );
    }

    /**
     * Executes the rule-guarded UpdateOrderItem command (G10 Außentür — the Rule chain runs structurally in the
     * dispatch).
     *
     * @param CommandUpdateOrderItem $updateOrderItem
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateOrderItem(
        CommandUpdateOrderItem $updateOrderItem,
        string $version = ''
    ): DomainResponseInterface {
        $denied = ($this->handle(EcommerceCheckAccess::class))(
            $this->entry(),
            $this->actor(),
            EcommerceRights::SALES_ORDER__UPDATEORDERITEM
        );
        if ($denied !== null) {
            return ($this->handle(AnswerThrowable::class))(
                new ContextResponse('UpdateOrderItem'),
                new TransformResponse($this->clock(), $version !== '' ? $version : $this->version()),
                $denied,
                true,
                'UpdateOrderItem'
            );
        }

        return ($this->handle(ProjectAnswer::class))(
            $this->context(UpdateOrderItem::class, $updateOrderItem, $version)(),
            'orderNumber',
            'orderItemIdentifier'
        );
    }

    /**
     * Executes the rule-guarded UpdateVoucher command (G10 Außentür — the Rule chain runs structurally in the
     * dispatch).
     *
     * @param CommandUpdateVoucher $updateVoucher
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateVoucher(CommandUpdateVoucher $updateVoucher, string $version = ''): DomainResponseInterface
    {
        $denied = ($this->handle(EcommerceCheckAccess::class))(
            $this->entry(),
            $this->actor(),
            EcommerceRights::SALES_VOUCHER__UPDATEVOUCHER
        );
        if ($denied !== null) {
            return ($this->handle(AnswerThrowable::class))(
                new ContextResponse('UpdateVoucher'),
                new TransformResponse($this->clock(), $version !== '' ? $version : $this->version()),
                $denied,
                true,
                'UpdateVoucher'
            );
        }

        return ($this->handle(ProjectAnswer::class))(
            $this->context(UpdateVoucher::class, $updateVoucher, $version)(),
            'voucherCode'
        );
    }
}
