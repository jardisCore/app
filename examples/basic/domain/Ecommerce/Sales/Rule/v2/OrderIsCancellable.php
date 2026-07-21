<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule\v2;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Aggregate\Order\Command\Order as CommandOrder;

/**
 * v2 of OrderIsCancellable (docs/rules-layer/PLAN.md AK8, PRD §5 Frage 10 /
 * M5): verschärft die Akzeptanzmenge — ab v2 ist neben 'cancelled' auch
 * 'shipped' nicht mehr stornierbar. Payload-Struktur ({rule, messageKey,
 * context}) und Message-Key bleiben UNVERAENDERT — Verhalten darf sich über
 * Rule-Versionen ändern, die API (Signatur + Ablehnungs-Payload) nicht.
 */
final class OrderIsCancellable extends EcommerceContext
{
    public function __invoke(CommandOrder $command): RuleResult
    {
        if ($command->orderNumber === 'ORDER-TRIGGER-FAULT') {
            throw new \RuntimeException('simulated technical fault in OrderIsCancellable');
        }
        if ($command->status === 'cancelled' || $command->status === 'shipped') {
            return RuleResult::reject(
                'OrderIsCancellable',
                'rule.order_is_cancellable.already_cancelled',
                ['status' => $command->status],
            );
        }

        return RuleResult::pass();
    }
}
