<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Aggregate\Order\Command\Order as CommandOrder;

/**
 * Rule: StockAvailable.
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
final class StockAvailable extends EcommerceContext
{
    /**
     * Test-fixture predicate (docs/rules-layer/PLAN.md P3 integration
     * tests). $invocationCount is a Kurzschluss probe: the chain in
     * Rules.yaml (Order: [OrderIsCancellable, StockAvailable]) must never
     * reach this rule once OrderIsCancellable has already rejected — the
     * AK2 test resets the counter and asserts it stays 0 in that case.
     */
    public static int $invocationCount = 0;

    public function __invoke(CommandOrder $command): RuleResult
    {
        self::$invocationCount++;

        // orderNumber sentinel (free-form VARCHAR, no enum constraint —
        // unlike status, never collides with field validation).
        if ($command->orderNumber === 'ORDER-OUT-OF-STOCK') {
            return RuleResult::reject(
                'StockAvailable',
                'rule.stock_available.out_of_stock',
                ['orderNumber' => $command->orderNumber],
            );
        }

        return RuleResult::pass();
    }
}
