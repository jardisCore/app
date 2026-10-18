<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure\v2;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Data\OrderStatus;
use Ecommerce\Sales\Entity\Order;
use Ecommerce\Sales\Closure\Data\RuleResult;

/**
 * v2 of OrderIsCancellable (docs/rules-layer/PLAN.md AK8, PRD §5 Frage 10 /
 * M5): verschärft die Akzeptanzmenge — ab v2 ist neben 'cancelled' auch
 * 'shipped' nicht mehr stornierbar. Payload-Struktur ({rule, messageKey,
 * context}) und Message-Key bleiben UNVERAENDERT — Verhalten darf sich über
 * Rule-Versionen ändern, die API (Signatur + Ablehnungs-Payload) nicht.
 *
 * rules-neuverortung PRD §8.1/Q4 (PLAN.md Phase 1.3): this hand-maintained
 * ClassVersion override is NOT touched by the build (there is no `Rule/v{N}/`
 * write path in pipeline/handler/build_rules.go) — its signature is kept in
 * lockstep with the v1 baseline by hand, here migrated to the `aggregate`
 * subject alongside it.
 */
final class OrderIsCancellable extends EcommerceContext
{
    public function __invoke(Order $root): RuleResult
    {
        if ($root->getOrderNumber() === 'ORDER-TRIGGER-FAULT') {
            throw new \RuntimeException('simulated technical fault in OrderIsCancellable');
        }
        if ($root->getStatus() === OrderStatus::Cancelled || $root->getStatus() === OrderStatus::Shipped) {
            return RuleResult::reject(
                'OrderIsCancellable',
                'rule.order_is_cancellable.already_cancelled',
                ['status' => $root->getStatus()?->value],
            );
        }

        return RuleResult::pass();
    }
}
