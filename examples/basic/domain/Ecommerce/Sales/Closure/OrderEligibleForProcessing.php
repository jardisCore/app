<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use JardisSupport\Response\Data\RuleResult;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;

/**
 * Rule: OrderEligibleForProcessing.
 *
 * M9: this Rule may read ONLY its OWN bounded context — no Fremd-BC
 * import, ever. A read of
 * another BC's state belongs in a Prozess-Knoten, not a Rule. The
 * read itself goes over the Kernel-Naht, not the Lese-Fassade — the
 * facade is the BC's API to the outside. Recipe: read a small page of
 * the list and decide via `total`/`items` — for example
 * `$this->context(Get{Agg}ListHandler::class, $this->handle({Agg}ListFilter::class, limit: 1))()`.
 * A scope-bound root (a `tenant` request-attribute column) makes the scope a
 * REQUIRED filter argument — `$this->handle({Agg}ListFilter::class, <scopeField>: $scope, limit: 1)`;
 * a read without it fails with an ArgumentCountError, never counts across scopes.
 * Need a different shape? Declare your own `internal` query
 * (`save_queries`), bind it in this Rule's `reads:` (`save_closures`), then
 * `build` — see Skill `platform-implementation`.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Closure: OrderEligibleForProcessing
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

    public function __invoke(CommandOrder $command): RuleResult
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
