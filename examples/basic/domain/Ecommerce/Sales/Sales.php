<?php

declare(strict_types=1);

namespace Ecommerce\Sales;

use Throwable;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Ecommerce\Sales\Aggregate\Invoice\InvoiceRead;
use Ecommerce\Sales\Aggregate\Order\OrderRead;
use Ecommerce\Sales\Process\SalesProcess;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder as CommandUpdateOrder;
use Ecommerce\Sales\Aggregate\Order\Command\Handler\UpdateOrder;

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
 *   $bc->process()->{process}(...)
 *   $bc->updateOrder($updateOrder)
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
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateOrder(CommandUpdateOrder $updateOrder): DomainResponseInterface
    {
        return $this->context(UpdateOrder::class, $updateOrder)();
    }
}
