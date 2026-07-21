<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Aggregate\Order\Command\Order as CommandOrder;

/**
 * Rule: OrderIsCancellable.
 *
 * Policy reference: POL-ORDER-CANCELLABLE.
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
final class OrderIsCancellable extends EcommerceContext
{
    /**
     * Test-fixture predicate (docs/rules-layer/PLAN.md P3 integration tests):
     * an order whose status is already 'cancelled' cannot be cancelled again
     * (both are real `orders.status` ENUM values — field validation must
     * pass BEFORE this rule ever runs, E2). The orderNumber sentinel
     * 'ORDER-TRIGGER-FAULT' deterministically throws — the AK2 fixture for
     * the 500 (technical fault, never 422) path; orderNumber is a free-form
     * VARCHAR, so this sentinel never collides with field validation.
     */
    public function __invoke(CommandOrder $command): RuleResult
    {
        if ($command->orderNumber === 'ORDER-TRIGGER-FAULT') {
            throw new \RuntimeException('simulated technical fault in OrderIsCancellable');
        }
        if ($command->status === 'cancelled') {
            return RuleResult::reject(
                'OrderIsCancellable',
                'rule.order_is_cancellable.already_cancelled',
                ['status' => $command->status],
            );
        }

        return RuleResult::pass();
    }
}
