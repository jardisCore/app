<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure\Guard;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\VoucherUpdateAllowed;
use JardisSupport\Response\Data\RuleResult;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\UpdateVoucher as CommandUpdateVoucher;

/**
 * Rules-Layer Guard-Closure for the UpdateVoucher command (docs/rules-layer/PLAN.md
 * Vertrag 2). Runs the bound AND-chain with Kurzschluss — the first
 * rejection stops the chain. Rules are dispatched via `$this->handle()`
 * (ClassVersion-fähig), never `new`.
 */
final class GuardUpdateVoucher extends EcommerceContext
{
    /**
     * @param CommandUpdateVoucher $updateVoucher
     * @throws Throwable
     */
    public function __invoke(CommandUpdateVoucher $updateVoucher): RuleResult
    {
        $result = $this->handle(VoucherUpdateAllowed::class)($updateVoucher);
        if (!$result->passed) {
            return RuleResult::reject($result->rule, 'sales.voucherUpdateNotAllowed', $result->context);
        }

        return RuleResult::pass();
    }
}
