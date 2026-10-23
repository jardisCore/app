<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Order;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\RuleGuardedOrderIntakeContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Handler\Node\MapResponse;
use Throwable;

/**
 * Node PlaceOrder of the process RuleGuardedOrderIntake (call).
 *
 * Ruft den Order-Aggregat-Command Order (createOrder) auf. onFail bei
 * 422 (Rule-Ablehnung der Order-eigenen Kette, Vertrag 5) — ein
 * technischer 5xx-Fehler geht NICHT ueber diese Kante, sondern in den
 * Exception-Pfad der Engine.
 *
 * Calls the facade Ecommerce.Sales.Order, method Order.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: RuleGuardedOrderIntake
 *     Rule-Knoten-/Vertrag-5-Fixture (docs/rules-layer/PLAN.md P5, A6;
 *     Set-Knoten-Golden seit rule-sets PLAN.md E1-P2, S9):
 *     demonstriert BEIDE Rules-Layer-Einsatzorte im selben Prozess.
 *
 *     (1) Ein Rule-Knoten prueft frueh im Flow (vor teurer Arbeit, PRD §5
 *     Frage 6) das Rule-Set OrderIntakeChecks (Mitglieder StockAvailable,
 *     OrderEligibleForProcessing) — DASSELBE Set, das auch die Order-Kette
 *     bindet (Rules.json) — AND-Kette mit Kurzschluss ueber den generierten
 *     Adapter (alle Mitglieder passed -> onSuccess, erstes rejected ->
 *     onFail mit additivem context.set).
 *
 *     (2) Erst danach ruft ein Custom-Knoten (Arbeits-Body, K2) den
 *     Order-Aggregat-Command Order (createOrder) auf, dessen EIGENE
 *     Rules.json-Kette (OrderIntakeChecks, OrderWithinCreditLimit) real
 *     422/500 liefern kann — Vertrag 5: 422 -> onFail (gemalte Kante), 5xx ->
 *     der Exception-Pfad der Engine (keine onFail-Kante, kein stiller
 *     Erfolg).
 * Knoten: PlaceOrder
 */
final class PlaceOrder extends EcommerceContext
{
    /**
     * @node-id n2
     * @throws Throwable
     */
    public function __invoke(RuleGuardedOrderIntakeContext $ctx): NodeResult
    {
        /** @var DomainResponseInterface $response */
        $response = $this->handle(Order::class)->createOrder($ctx->input()->order);

        return (new MapResponse())($response);
    }
}
