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
