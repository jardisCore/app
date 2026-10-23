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
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: OrderInvoiceUnionAlert
 *     Belegt AK2 (Event-Feld-Bindung, Union-Quelle, PRD
 *     docs/process-event-fields/PRD.md §5) am End-to-End-Golden-Pfad:
 *     subjectRemoval referenziert Order.RemoveOrder | Invoice.RemoveInvoice —
 *     zwei Root-Ops verschiedener Aggregate derselben BC mit gleicher
 *     Identitaets-Kettenlaenge (Aritaet 1, V-EVT-ARITY-konform). Der
 *     Event-Feld-Resolver loest je Zweig die eigene Root-Identitaet auf
 *     (match(true)+instanceof, P2 Renderer + P4.5 Multi-Aggregat-Config-Fix).
 * Knoten: SubjectRemovalAlertedNode
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
