<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure\Guard;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Closure\InvoiceNotYetPaid;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\Invoice as CommandInvoice;

/**
 * Rules-Layer Guard-Closure for the Invoice command (docs/rules-layer/PLAN.md
 * Vertrag 2). Runs the bound AND-chain with Kurzschluss — the first
 * rejection stops the chain. Rules are dispatched via `$this->handle()`
 * (ClassVersion-fähig), never `new`.
 */
final class GuardInvoice extends EcommerceContext
{
    /**
     * @param CommandInvoice $invoice
     * @throws Throwable
     */
    public function __invoke(CommandInvoice $invoice): RuleResult
    {
        $result = $this->handle(InvoiceNotYetPaid::class)($invoice);
        if (!$result->passed) {
            return $result;
        }

        return RuleResult::pass();
    }
}
