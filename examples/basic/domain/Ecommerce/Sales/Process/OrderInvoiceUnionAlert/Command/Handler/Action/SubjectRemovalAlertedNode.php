<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Command\RemoveInvoice;
use Ecommerce\Sales\Model\Order\Command\RemoveOrder;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\OrderInvoiceUnionAlertContext;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Event\SubjectRemovalAlerted;
use JardisSupport\Data\Identity;
use JardisSupport\Scheduling\Clock\SystemClock;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\Route;
use Throwable;

/**
 * Node SubjectRemovalAlertedNode of the process OrderInvoiceUnionAlert (action).
 */
final class SubjectRemovalAlertedNode extends EcommerceContext
{
    /**
     * @node-id v2b3c402
     * @throws Throwable
     */
    public function __invoke(OrderInvoiceUnionAlertContext $ctx): NodeResult
    {
        $removal = $ctx->input()->subjectRemoval;

        return NodeResult::route(Route::OnTrue)->withEvents(new SubjectRemovalAlerted(
            removedOrderId: match (true) {
                $removal instanceof RemoveOrder => $removal->orderNumber,
                $removal instanceof RemoveInvoice => $removal->invoiceIdentifier,
            },
            eventId: $this->handle(Identity::class)->generateUuid7(),
            occurredAt: $this->handle(SystemClock::class)->now(),
        ));
    }
}
