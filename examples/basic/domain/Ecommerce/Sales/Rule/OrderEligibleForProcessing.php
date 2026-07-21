<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;

/**
 * Rule: OrderEligibleForProcessing.
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
final class OrderEligibleForProcessing extends EcommerceContext
{
    /**
     * Test-fixture predicate (docs/rules-layer/PLAN.md P5 integration tests,
     * AK3): this Rule is deliberately UNBOUND (no endpoint binding,
     * HasCommand=false) — it exists purely to exercise the Rule-node adapter
     * itself, so its predicate never needs to read $command's shape. A
     * static toggle drives pass/reject deterministically.
     */
    public static bool $reject = false;

    public function __invoke(object $command): RuleResult
    {
        if (self::$reject) {
            return RuleResult::reject(
                'OrderEligibleForProcessing',
                'rule.order_eligible_for_processing.ineligible',
                [],
            );
        }

        return RuleResult::pass();
    }
}
