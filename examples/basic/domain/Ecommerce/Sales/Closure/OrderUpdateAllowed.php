<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder as CommandUpdateOrder;

/**
 * Rule: OrderUpdateAllowed.
 *
 * M9: this Rule may read ONLY its OWN bounded context — no Fremd-BC
 * import, ever. A read of
 * another BC's state belongs in a Prozess-Knoten, not a Rule. The
 * read itself goes over the Kernel-Naht, not the Lese-Fassade — the
 * facade is the BC's API to the outside. Recipe: read a small page of
 * the list and decide via `total`/`items` — for example
 * `$this->context(Get{Agg}ListHandler::class, new {Agg}ListFilter(limit: 1))()`.
 * A scope-bound root (a `tenant` request-attribute column) makes the scope a
 * REQUIRED filter argument — `new {Agg}ListFilter(<scopeField>: $scope, limit: 1)`;
 * a read without it fails with an ArgumentCountError, never counts across scopes.
 * Need a different shape? Declare your own `internal` query
 * (`save_queries`), bind it in this Rule's `reads:` (`save_closures`), then
 * `build` — see Skill `platform-implementation`.
 */
final class OrderUpdateAllowed extends EcommerceContext
{
    /**
     * $invocationCount is a Kurzschluss probe (rules-neuverortung PLAN.md
     * Phase 1.3 Zusage 6): UpdateOrder's chain is now the gemischte Kette
     * [OrderIsCancellable (aggregate), OrderUpdateAllowed (command)] — this
     * rule must never run once OrderIsCancellable has already rejected.
     */
    public static int $invocationCount = 0;

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
        self::$invocationCount++;

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
