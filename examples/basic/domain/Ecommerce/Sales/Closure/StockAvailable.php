<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;

/**
 * Rule: StockAvailable.
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
final class StockAvailable extends EcommerceContext
{
    /**
     * Test-fixture predicate (docs/rules-layer/PLAN.md P3 integration
     * tests). $invocationCount is a Kurzschluss probe. Since
     * rules-neuverortung Phase 1.3 (OrderIsCancellable moved to
     * UpdateOrder's aggregate-subject binding) Order's own create chain is
     * `[StockAvailable]` alone — the probe now documents that a REJECTED
     * field-validation (400) or an unrelated technical fault never still
     * increments it (E2: Validate strictly precedes the guard call).
     *
     * The orderNumber sentinel 'ORDER-CREATE-TRIGGER-FAULT' (a NEW, distinct
     * sentinel — RuleGuardedOrderIntakeTest / RoutesEnvelopeTest need a
     * deterministic 500-technical-fault trigger reachable through Order's
     * CREATE chain; 'ORDER-TRIGGER-FAULT' itself stays reserved for
     * OrderIsCancellable's own UPDATE-time fault, RulesGuardOrderTest — the
     * two must NOT collide, since RulesGuardOrderTest first CREATEs the
     * order it later updates) throws.
     */
    public static int $invocationCount = 0;

    /**
     * @throws \RuntimeException
     */
    public function __invoke(CommandOrder $command): RuleResult
    {
        self::$invocationCount++;

        if ($command->orderNumber === 'ORDER-CREATE-TRIGGER-FAULT') {
            throw new \RuntimeException('simulated technical fault in StockAvailable');
        }

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
