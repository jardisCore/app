<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use JardisSupport\Response\Data\RuleResult;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;

/**
 * Rule: OrderWithinCreditLimit.
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
 * Closure: OrderWithinCreditLimit
 */
final class OrderWithinCreditLimit extends EcommerceContext
{
    /**
     * Test-fixture predicate (rule-sets PLAN.md E1-P2, S7 Golden-Fixture):
     * the EINZELGLIED behind the Set OrderIntakeChecks in the Order chain —
     * exists so GuardOrder.php shows three handle() calls (two via the Set,
     * one direct) and so a rejection WITHOUT `context.set` can be observed
     * next to the Set-mediated ones. A static toggle drives pass/reject
     * deterministically; the predicate never reads $command's shape.
     */
    public static bool $reject = false;

    public function __invoke(CommandOrder $command): RuleResult
    {
        if (self::$reject) {
            return RuleResult::reject(
                'OrderWithinCreditLimit',
                'sales.orderWithinCreditLimit',
                [],
            );
        }

        return RuleResult::pass();
    }
}
