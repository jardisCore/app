<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Data\OrderStatus;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order;
use JardisSupport\Response\Data\RuleResult;

/**
 * Rule: OrderIsCancellable.
 *
 * Message key: sales.orderAlreadyCancelled.
 * The 422 answer carries this catalog key, not the key passed to reject().
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
 * Closure: OrderIsCancellable
 */
final class OrderIsCancellable extends EcommerceContext
{
    /**
     * Test-fixture predicate (rules-neuverortung PRD §8.1, PLAN.md Phase
     * 1.3): `aggregate`-subject since this Phase — an order whose status is
     * already 'cancelled' cannot be cancelled again, judged directly on the
     * already-loaded root Entity (both are real `orders.status` ENUM
     * values — field validation must pass BEFORE this rule ever runs, E2).
     * The orderNumber sentinel 'ORDER-TRIGGER-FAULT' deterministically
     * throws — the AK2 fixture for the 500 (technical fault, never 422)
     * path; orderNumber is a free-form VARCHAR, so this sentinel never
     * collides with field validation.
     * @throws \RuntimeException
     */
    public function __invoke(Order $root): RuleResult
    {
        if ($root->getOrderNumber() === 'ORDER-TRIGGER-FAULT') {
            throw new \RuntimeException('simulated technical fault in OrderIsCancellable');
        }
        if ($root->getStatus() === OrderStatus::Cancelled) {
            return RuleResult::reject(
                'OrderIsCancellable',
                'rule.order_is_cancellable.already_cancelled',
                ['status' => $root->getStatus()?->value],
            );
        }

        return RuleResult::pass();
    }
}
