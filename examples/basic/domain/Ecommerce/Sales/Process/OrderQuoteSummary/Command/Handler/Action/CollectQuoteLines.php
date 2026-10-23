<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\OrderQuoteSummaryContext;
use Ecommerce\Sales\Process\OrderQuoteSummary\Response\CollectQuoteLinesResponse;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node CollectQuoteLines of the process OrderQuoteSummary (action).
 *
 * Erster Knoten: liefert Waehrung und eine vorlaeufige Positionszahl.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: OrderQuoteSummary
 *     Antwortform `merged` (tuer-und-response E4 P5b): zwei Action-Knoten, die
 *     Antwort sind die Daten ALLER Knoten in Laufreihenfolge, bei gleichem Feldnamen
 *     gewinnt der spaetere. n1 liefert `currency` und `lineCount`, n2 liefert
 *     `lineCount` (ueberschreibt n1) und `total`. Kein `successOutput`.
 * Knoten: CollectQuoteLines
 */
final class CollectQuoteLines extends EcommerceContext
{
    /**
     * @node-id n1
     */
    public function __invoke(OrderQuoteSummaryContext $ctx): NodeResult
    {
        // Dev body (golden): fixed values, the node answers currency and a first line count.
        return NodeResult::ok(new CollectQuoteLinesResponse(currency: 'EUR', lineCount: 2));
    }
}
