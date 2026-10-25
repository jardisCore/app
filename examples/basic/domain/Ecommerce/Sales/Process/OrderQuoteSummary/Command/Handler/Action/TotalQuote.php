<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderQuoteSummary\Command\Handler\OrderQuoteSummaryContext;
use Ecommerce\Sales\Process\OrderQuoteSummary\Response\TotalQuoteResponse;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node TotalQuote of the process OrderQuoteSummary (action).
 *
 * Zweiter Knoten: liefert die endgueltige Positionszahl (ueberschreibt n1)
 * und die Summe.
 *
 * Mandant und Nutzer des Laufs: $ctx->input()->tenant, $ctx->input()->user (beide string).
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: OrderQuoteSummary
 *     Antwortform `merged` (tuer-und-response E4 P5b): zwei Action-Knoten, die
 *     Antwort sind die Daten ALLER Knoten in Laufreihenfolge, bei gleichem Feldnamen
 *     gewinnt der spaetere. n1 liefert `currency` und `lineCount`, n2 liefert
 *     `lineCount` (ueberschreibt n1) und `total`. Kein `successOutput`.
 * Knoten: TotalQuote
 */
final class TotalQuote extends EcommerceContext
{
    /**
     * @node-id n2
     */
    public function __invoke(OrderQuoteSummaryContext $ctx): NodeResult
    {
        // Dev body (golden): fixed values, the node overwrites lineCount and adds the total.
        return NodeResult::ok(new TotalQuoteResponse(lineCount: 3, total: '42.50'));
    }
}
