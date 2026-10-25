<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderBulkQuote\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderBulkQuote\Command\Handler\OrderBulkQuoteContext;
use Ecommerce\Sales\Process\OrderBulkQuote\Response\PriceBulkQuoteResponse;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node PriceBulkQuote of the process OrderBulkQuote (action).
 *
 * Bepreist die Angebotspositionen. Der Koerper ist Dev-Code; der
 * Eingang ist vor dem ersten Knoten vollstaendig geprueft.
 *
 * Mandant und Nutzer des Laufs: $ctx->input()->tenant, $ctx->input()->user (beide string).
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: OrderBulkQuote
 *     Strukturierter Prozess-Eingang (FIX3, R942): eine Skalarliste, eine
 *     nullbare Werteliste-Liste, ein Objekt und eine Objektliste, deren Elemente
 *     ein nullbares Objekt (Ebene 2) und eine nullbare Skalarliste tragen. Jedes
 *     Element ist typisiert; ein falsches Element antwortet 400 mit seinem
 *     Index-Pfad, nie 500.
 * Knoten: PriceBulkQuote
 */
final class PriceBulkQuote extends EcommerceContext
{
    /**
     * @node-id q1w2e3r4
     */
    public function __invoke(OrderBulkQuoteContext $ctx): NodeResult
    {
        // Dev body (golden): reads the typed input tree the gate has checked.
        $input = $ctx->input();
        $discounted = 0;
        foreach ($input->lines as $line) {
            if ($line->discount !== null) {
                $discounted++;
            }
        }

        return NodeResult::ok(new PriceBulkQuoteResponse(
            lineCount: count($input->lines),
            discountedLines: $discounted,
            city: $input->shippingAddress->city,
            voucherCount: count($input->voucherCodes),
        ));
    }
}
