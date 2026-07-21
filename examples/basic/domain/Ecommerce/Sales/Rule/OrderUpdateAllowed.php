<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder as CommandUpdateOrder;

/**
 * Rule: OrderUpdateAllowed.
 *
 * Policy reference: POL-ORDER-UPDATE-ALLOWED.
 *
 * M9: this Rule may read ONLY the Lese-Fassade of its OWN bounded
 * context (`$this->handle(SelfBc::class)->{agg}()`) — no Fremd-BC
 * import, ever (docs/rules-layer/PRD.md §5 Frage 3+7). A read of
 * another BC's state belongs in a Prozess-Knoten, not a Rule.
 *
 * A12 (Kombi-/OR-Rules): compose atomic catalog Rules via
 * `$this->handle(OtherRule::class)($command)` (ClassVersion-fähig) —
 * never `new OtherRule()`.
 */
final class OrderUpdateAllowed extends EcommerceContext
{
    /**
     * Test-fixture predicate (docs/rules-layer/PLAN.md P4 integration tests,
     * AK4): an update carrying a non-positive totalAmount is rejected — a
     * real business check (field validation only enforces the DTO's `float`
     * type, not positivity). Proves the guard chain runs for every caller of
     * UpdateOrder, including the G10 Außentür method (E3) — the guard call
     * lives inside the generated CommandHandler, not at the caller's site.
     */
    public function __invoke(CommandUpdateOrder $command): RuleResult
    {
        if ($command->totalAmount <= 0.0) {
            return RuleResult::reject(
                'OrderUpdateAllowed',
                'rule.order_update_allowed.non_positive_total',
                ['totalAmount' => $command->totalAmount],
            );
        }

        return RuleResult::pass();
    }
}
