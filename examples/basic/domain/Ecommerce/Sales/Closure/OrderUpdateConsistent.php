<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order;
use JardisSupport\Response\Data\RuleResult;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder as CommandUpdateOrder;

/**
 * Zwei-Eingang-Closure (docs/closure-mit-vertrag/PLAN-E2.md Phase E2.3 Zusage 3): braucht BEIDE Eingaenge — lehnt eine
 * No-Op-Aenderung ab, deren totalAmount UND status mit dem schon geladenen Order-Wurzelzustand identisch sind (ein
 * Update muss etwas aendern).
 *
 * Closure: OrderUpdateConsistent — Ausgang: Urteil (RuleResult).
 *
 * Message key: sales.orderUpdateInconsistent —
 * RuleResult::reject('OrderUpdateConsistent', 'sales.orderUpdateInconsistent', [...]).
 * The 422 answer carries the catalog key, not the key passed to reject().
 *
 * Leitplanken: der Vertrag ist ausschließlich __invoke — kein payload() als
 * Eingang, resource() nie zum Schreiben.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Closure: OrderUpdateConsistent
 */
final class OrderUpdateConsistent extends EcommerceContext
{
    /**
     * Deliberately NOT the catalog `messageKey` (sales.orderUpdateInconsistent):
     * the catalog key is binding, so every door answers with the catalog key and
     * never with this one (Bescheid Rolf 2026-10-13, Weg A) —
     * RulesGuardTwoInputTest pins exactly that.
     */
    public const BODY_MESSAGE_KEY = 'rule.order_update_consistent.no_op';

    /**
     * Static probes ("Closure mit Vertrag" docs/closure-mit-vertrag/
     * PLAN-E2.md Phase E2.3 Zusage 3/4, RulesGuardTwoInputTest.php):
     * $lastCommandOrderNumber/$lastRootOrderNumber record what each
     * declared input actually was, proving a Guard-bound TWO-input
     * closure receives both — $command (the UpdateOrder DTO, a plain
     * property) and $root (the already-loaded Order aggregate, a
     * getter-only Entity) — in declaration order.
     */
    public static int $invocationCount = 0;
    public static ?string $lastCommandOrderNumber = null;
    public static ?string $lastRootOrderNumber = null;

    /**
     * Test-fixture predicate: rejects a no-op update whose totalAmount AND
     * status are both identical to the already-loaded root — needs BOTH
     * inputs to judge. Every existing PHP integration test that reaches
     * UpdateOrder's guard chain changes at least one of the two against the
     * seeded root, so this never rejects any of them —
     * RulesGuardTwoInputTest.php is the only caller that deliberately
     * submits a no-op.
     */
    public function __invoke(CommandUpdateOrder $command, Order $root): RuleResult
    {
        self::$invocationCount++;
        self::$lastCommandOrderNumber = $command->orderNumber;
        self::$lastRootOrderNumber = $root->getOrderNumber();

        if ($command->totalAmount === $root->getTotalAmount() && $command->status === $root->getStatus()?->value) {
            return RuleResult::reject(
                'OrderUpdateConsistent',
                self::BODY_MESSAGE_KEY,
                ['totalAmount' => $command->totalAmount, 'status' => $command->status],
            );
        }

        return RuleResult::pass();
    }
}
