<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\OrderInvoiceUnionAlertContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node EvaluateSubjectRemoval of the process OrderInvoiceUnionAlert (action).
 *
 * Platzhalter fuer die Entfernungs-Pruefung.
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
 * Knoten: EvaluateSubjectRemoval
 */
final class EvaluateSubjectRemoval extends EcommerceContext
{
    /**
     * @node-id v1a2b301
     */
    public function __invoke(OrderInvoiceUnionAlertContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}
