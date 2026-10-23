<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order;
use JardisSupport\Response\Data\RuleResult;

/**
 * Test-fixture predicate (Access L7, Rule-Knoten-Belegung je Eingang): ein
 * Verdict mit ZWEI Eingaengen verschiedener Art - die geladene Order-Wurzel
 * (`aggregate`) und ein Betragslimit (`scalar`). Weder die Kurzform
 * `ruleSubject` noch ein Guard kann beides befuellen; ein Rule-Knoten belegt
 * beide einzeln ueber `ruleArgs`.
 *
 * Closure: OrderTotalWithinLimit — Ausgang: Urteil (RuleResult).
 *
 * Message key: sales.orderOverLimit —
 * RuleResult::reject('OrderTotalWithinLimit', 'sales.orderOverLimit', [...]).
 * The 422 answer carries the catalog key, not the key passed to reject().
 *
 * Leitplanken: der Vertrag ist ausschließlich __invoke — kein payload() als
 * Eingang, resource() nie zum Schreiben.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Closure: OrderTotalWithinLimit
 */
final class OrderTotalWithinLimit extends EcommerceContext
{
    /**
     * Test-fixture predicate: the order total must not exceed the limit.
     * Both values are decimal strings; the comparison is by float value, which
     * is exact enough for the fixture amounts.
     */
    public function __invoke(Order $root, string $limit): RuleResult
    {
        $total = (string) $root->getTotalAmount();
        if ((float) $total > (float) $limit) {
            return RuleResult::reject(
                'OrderTotalWithinLimit',
                'rule.order_total_within_limit.over_limit',
                ['total' => $total, 'limit' => $limit],
            );
        }

        return RuleResult::pass();
    }
}
