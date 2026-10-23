<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherOverview\Command\Handler;

use Ecommerce\Sales\Process\VoucherOverview\Command\Handler\Action\ListVouchers;
use Ecommerce\Sales\Process\VoucherOverview\Command\VoucherOverview;
use Ecommerce\Sales\Process\VoucherOverview\Response\ListVouchersResponse;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process VoucherOverview: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class VoucherOverviewDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(VoucherOverview::class, 'VoucherOverview')
            ->context(VoucherOverviewContext::class)
            ->transactional(true)
            ->node(ListVouchers::class)
            ->terminal(Route::OnTrue)
            ->rejects(ResponseStatus::NotFound, 'sales.voucherScope.notFound')
            ->response(ListVouchersResponse::class, 'vouchers')
            ->build();
    }
}
