<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherOverview\Command\Handler;

use Ecommerce\Sales\Process\VoucherOverview\Command\Handler\Action\ListVouchers;
use Ecommerce\Sales\Process\VoucherOverview\Command\VoucherOverview;
use Ecommerce\Sales\Process\VoucherOverview\Response\ListVouchersResponse;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process VoucherOverview: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class VoucherOverviewContext extends ProcessContext
{
    public function input(): VoucherOverview
    {
        /** @var VoucherOverview $input */
        $input = parent::input();

        return $input;
    }

    public function listVouchers(): ?ListVouchersResponse
    {
        $data = $this->latest(ListVouchers::class)?->data();

        return $data instanceof ListVouchersResponse ? $data : null;
    }
}
