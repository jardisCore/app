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
