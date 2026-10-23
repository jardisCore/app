<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\OrderLimitCheckContext;
use Ecommerce\Sales\Process\OrderLimitCheck\Response\ResolveOrderNumberResponse;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node ResolveOrderNumber of the process OrderLimitCheck (action).
 *
 * Handgeschriebener Vorknoten: liefert die Bestellnummer als Ergebnis-Feld
 * (`outputShape`), aus dem der Rule-Knoten die Wurzel laedt.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: OrderLimitCheck
 *     Access L7 - Rule-Knoten belegt JEDEN Eingang der Closure (`ruleArgs`):
 *     Knoten n1 (handgeschrieben, `outputShape` mit `orderNumber`) loest die
 *     Bestellnummer auf; der Rule-Knoten n2 ruft OrderTotalWithinLimit(root:
 *     aggregate Order, limit: scalar decimal) - die Wurzel wird aus dem Ergebnis
 *     von n1 geladen (`latest`, Feld `orderNumber`), das Limit kommt aus dem
 *     Prozess-Eingang (`input`). Existiert die Order nicht -> ON_FAIL notFound,
 *     ueberschreitet sie das Limit -> ON_FAIL 422 `sales.orderOverLimit`.
 * Knoten: ResolveOrderNumber
 */
final class ResolveOrderNumber extends EcommerceContext
{
    /** Test switch (fixture): when true the node returns no `orderNumber` in its result data. */
    public static bool $omitResult = false;

    /**
     * @node-id n1
     */
    public function __invoke(OrderLimitCheckContext $ctx): NodeResult
    {
        // Test switch: a node that hands on no typed result (the L7 "A ohne Ergebnis" case).
        if (self::$omitResult) {
            return NodeResult::ok([]);
        }

        return NodeResult::ok(new ResolveOrderNumberResponse(orderNumber: $ctx->input()->orderNumberToCheck));
    }
}
