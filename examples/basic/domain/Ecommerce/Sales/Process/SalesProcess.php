<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process;

use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\BatchOrderFulfilment;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\BatchOrderFulfilmentHandler;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\CrossBcReadDemo;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler\CrossBcReadDemoHandler;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\CrossBcServiceDemoHandler;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoHandler;
use Ecommerce\Sales\Process\OrderBulkQuote\Command\Handler\OrderBulkQuoteHandler;
use Ecommerce\Sales\Process\OrderBulkQuote\Command\OrderBulkQuote;
use Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler\OrderCancellableCheckHandler;
use Ecommerce\Sales\Process\OrderCancellableCheck\Command\OrderCancellableCheck;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchHandler;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\OrderInvoiceUnionAlertHandler;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\OrderInvoiceUnionAlert;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler\OrderLimitAfterPlaceHandler;
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\OrderLimitAfterPlace;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler\OrderLimitCheckHandler;
use Ecommerce\Sales\Process\OrderLimitCheck\Command\OrderLimitCheck;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\RuleGuardedOrderIntakeHandler;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
use Ecommerce\Sales\Process\VoucherRedeemCheck\Command\Handler\VoucherRedeemCheckActor;
use Ecommerce\Sales\Process\VoucherRedeemCheck\Command\Handler\VoucherRedeemCheckHandler;
use Ecommerce\Sales\Process\VoucherRedeemCheck\Command\VoucherRedeemCheck;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Ecommerce\EcommerceContext;

/**
 * SalesProcess — bounded-context process facade.
 *
 * Bundles every process of the bounded context behind one facade,
 * reached via $bc->process(). Each method dispatches to the process
 * orchestrator. Fully generated — overwritten on every rebuild; the
 * process logic lives in the orchestrator handler and its Action nodes,
 * not here.
 */
final class SalesProcess extends EcommerceContext
{
    /**
     * Caller-Fixture fuer R10: ruft OrderInvoiceBatch als Sub-Prozess auf.
     * Belegt R4 (Einzel-Command-Feld primaryCommand: UpdateOrder|UpdateInvoice),
     * R5 (Listen-Command-Feld lineCommands: array<AddOrderItem|AddInvoiceLine>)
     * und R1 (Skalare requestedBy: string, note: string).
     *
     */
    public function batchOrderFulfilment(BatchOrderFulfilment $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(BatchOrderFulfilmentHandler::class, $in, $version)();
    }

    /**
     * Cross-BC-Lese-Fixture (crm-nachlauf E3.9): ein Lese-Knoten mit consumedCalls
     * auf die Lese-Fassade einer fremden BC (Ecommerce.Sales -> Ecommerce.Catalog)
     * OHNE ausdruecklichen crossBcCall. Die Knoten-Art ist BC-bewusst: der Knoten ist
     * ein Cross-BC-Knoten, sein Service-Name wird abgeleitet ({Process}{Node}), die
     * Action importiert nichts aus Catalog und delegiert ueber die Kernel-Naht an das
     * Service-Scaffold, das die Lese-Fassade des fremden BC verdrahtet.
     *
     */
    public function crossBcReadDemo(CrossBcReadDemo $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(CrossBcReadDemoHandler::class, $in, $version)();
    }

    /**
     * Cross-BC-Write-Fixture (bc-facade-layering PRD.md G7, P4, AK3-Teil 2): zwei
     * Cross-BC-Call-Knoten belegen beide Ablageort-Faelle aus D3 —
     * CheckStockInCatalog schreibt in eine fremde BC IM SELBEN Domain
     * (Ecommerce.Sales -> Ecommerce.Catalog), CheckMeterReadingInMeterDevice
     * schreibt in eine fremde BC in einer ANDEREN Domain (Ecommerce.Sales ->
     * MeterDevice.Counter). D3 kollabiert auf denselben Ablageort — beide
     * Service-Stubs landen in Domain/Ecommerce/Service/.
     *
     * Ziele sind Prozesse, nicht Aggregate: Fremd-Writes laufen ueber den
     * process() des fremden BC — Ecommerce.Catalog.UpdateProductInCatalog kapselt
     * Product.UpdateProduct, MeterDevice.Counter.RecordCounterUpdate kapselt
     * Counter.UpdateCounter. Die echte DTO-Uebersetzung lebt jetzt im
     * Domain-Service (P4).
     *
     * Happy Path: CheckStockInCatalog fuehrt den Cross-BC-Write auf Catalog aus
     * und terminiert bei Erfolg — seit format-haertung A3 explizit per
     * `terminal: true` deklariert (CrossBcCall ist laut Emissions-Matrix ein
     * "formal freier" Knotentyp, den der Builder nicht automatisch erschoepfend
     * fuellt; die Builder-Emission strictRouting verlangt sonst eine gemappte
     * onTrue-Kante). Nur auf onFail wird die
     * Cross-Domain-Variante als Ausweichpfad betreten; CrossBcServiceDemoFinalize
     * bleibt der Fehler-Abschluss-Stub (KI-/Dev-Hoheit).
     *
     */
    public function crossBcServiceDemo(CrossBcServiceDemo $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(CrossBcServiceDemoHandler::class, $in, $version)();
    }

    /**
     * Externer-Call-Fixture (docs/cross-bc-process-access P4, AK2): drei
     * Externer-Call-Knoten belegen alle drei Ablageorte aus PLAN.md D1/D2/D7 —
     * CallPricingApiProcess (Ebene process, Default), CallPricingApiBc (Ebene
     * bc), CallPricingApiDomain (Ebene domain). Jeder Knoten deklariert einen
     * Service mit demselben Namen wie der Knoten selbst (Alias-Guard-Beleg,
     * analog CrossBcServiceDemo P3).
     *
     */
    public function externalCallServiceDemo(ExternalCallServiceDemo $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(ExternalCallServiceDemoHandler::class, $in, $version)();
    }

    /**
     * Strukturierter Prozess-Eingang (FIX3, R942): eine Skalarliste, eine
     * nullbare Werteliste-Liste, ein Objekt und eine Objektliste, deren Elemente
     * ein nullbares Objekt (Ebene 2) und eine nullbare Skalarliste tragen. Jedes
     * Element ist typisiert; ein falsches Element antwortet 400 mit seinem
     * Index-Pfad, nie 500.
     *
     */
    public function orderBulkQuote(OrderBulkQuote $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(OrderBulkQuoteHandler::class, $in, $version)();
    }

    /**
     * Kleinste Fixture-Ergaenzung fuer den notFound-Beweis am Rule-Knoten
     * (rules-neuverortung PRD I1/Sec8.3, PLAN.md Phase 1.3 Zusage 6): ein
     * eigener, winziger Prozess statt eines zweiten Knotens in
     * RuleGuardedOrderIntake, um die vier bestehenden RuleGuardedOrderIntakeTest-
     * Faelle nicht anzufassen. Der einzige Knoten referenziert die
     * `aggregate`-subject Katalog-Rule OrderIsCancellable (Einsatz, zusaetzlich
     * zu ihrem Wirkort an UpdateOrder, M7 Doppelbindung) mit einem skalaren
     * Payload-Feld als Wurzel-Identifier. Existiert der Order nicht, laedt der
     * emittierte Load-Closure null -> ON_FAIL mit dem festen
     * `{bc}.{agg}.notFound`-messageKey (Q6), nie eine echte Rule-Ablehnung.
     *
     */
    public function orderCancellableCheck(OrderCancellableCheck $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(OrderCancellableCheckHandler::class, $in, $version)();
    }

    /**
     * Typisierter Batch-Eingang (R3): das Input-DTO komponiert Aggregat-Commands
     * seines BC als Union — ein Einzel-Command-Feld und ein Listen-Command-Feld,
     * beide cross-aggregat (Order + Invoice). Skalar-Felder gemischt; die
     * Dispatch-Schleife bleibt Dev-Code im Knoten (R7), nicht Generat.
     *
     */
    public function orderInvoiceBatch(OrderInvoiceBatch $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(OrderInvoiceBatchHandler::class, $in, $version)();
    }

    /**
     * Belegt AK2 (Event-Feld-Bindung, Union-Quelle, PRD
     * docs/process-event-fields/PRD.md §5) am End-to-End-Golden-Pfad:
     * subjectRemoval referenziert Order.RemoveOrder | Invoice.RemoveInvoice —
     * zwei Root-Ops verschiedener Aggregate derselben BC mit gleicher
     * Identitaets-Kettenlaenge (Aritaet 1, V-EVT-ARITY-konform). Der
     * Event-Feld-Resolver loest je Zweig die eigene Root-Identitaet auf
     * (match(true)+instanceof, P2 Renderer + P4.5 Multi-Aggregat-Config-Fix).
     *
     */
    public function orderInvoiceUnionAlert(OrderInvoiceUnionAlert $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(OrderInvoiceUnionAlertHandler::class, $in, $version)();
    }

    /**
     * Access L7 - Zweitfall: die Quelle `latest` ist ein COMMAND-Knoten (Vorbild
     * Event-Feld-Identitaetskette). n1 legt die Order an (K2-Arbeits-Body auf
     * Order.Order); der Rule-Knoten n2 laedt die Wurzel aus dem Identitaetsfeld
     * `orderNumber` im Ergebnis von n1 (`facade`+`method` benennen den Aufruf) und
     * prueft sie gegen das Limit aus dem Prozess-Eingang.
     *
     */
    public function orderLimitAfterPlace(OrderLimitAfterPlace $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(OrderLimitAfterPlaceHandler::class, $in, $version)();
    }

    /**
     * Access L7 - Rule-Knoten belegt JEDEN Eingang der Closure (`ruleArgs`):
     * Knoten n1 (handgeschrieben, `outputShape` mit `orderNumber`) loest die
     * Bestellnummer auf; der Rule-Knoten n2 ruft OrderTotalWithinLimit(root:
     * aggregate Order, limit: scalar decimal) - die Wurzel wird aus dem Ergebnis
     * von n1 geladen (`latest`, Feld `orderNumber`), das Limit kommt aus dem
     * Prozess-Eingang (`input`). Existiert die Order nicht -> ON_FAIL notFound,
     * ueberschreitet sie das Limit -> ON_FAIL 422 `sales.orderOverLimit`.
     *
     */
    public function orderLimitCheck(OrderLimitCheck $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(OrderLimitCheckHandler::class, $in, $version)();
    }

    /**
     * Rule-Knoten-/Vertrag-5-Fixture (docs/rules-layer/PLAN.md P5, A6;
     * Set-Knoten-Golden seit rule-sets PLAN.md E1-P2, S9):
     * demonstriert BEIDE Rules-Layer-Einsatzorte im selben Prozess.
     *
     * (1) Ein Rule-Knoten prueft frueh im Flow (vor teurer Arbeit, PRD §5
     * Frage 6) das Rule-Set OrderIntakeChecks (Mitglieder StockAvailable,
     * OrderEligibleForProcessing) — DASSELBE Set, das auch die Order-Kette
     * bindet (Rules.json) — AND-Kette mit Kurzschluss ueber den generierten
     * Adapter (alle Mitglieder passed -> onSuccess, erstes rejected ->
     * onFail mit additivem context.set).
     *
     * (2) Erst danach ruft ein Custom-Knoten (Arbeits-Body, K2) den
     * Order-Aggregat-Command Order (createOrder) auf, dessen EIGENE
     * Rules.json-Kette (OrderIntakeChecks, OrderWithinCreditLimit) real
     * 422/500 liefern kann — Vertrag 5: 422 -> onFail (gemalte Kante), 5xx ->
     * der Exception-Pfad der Engine (keine onFail-Kante, kein stiller
     * Erfolg).
     *
     */
    public function ruleGuardedOrderIntake(RuleGuardedOrderIntake $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(RuleGuardedOrderIntakeHandler::class, $in, $version)();
    }

    /**
     * Request-Attribut-Felder an der Prozess-Tuer (pm-tool FIX1-G1b): der
     * Mandant (`tenant`) und der Akteur (`subject`) kommen aus dem Request-Attribut,
     * nie vom Client - beide fehlen im Body und im OpenAPI-Request. Der Rule-Knoten
     * laedt die mandanten-gescopte Voucher-Wurzel ueber LoadVoucherForRule im
     * Mandanten des Prozesskontexts; der Akteur ist nur Wert, nie Lesefilter (B4).
     * FIX3-h: `subjectId` (nackte Akteur-Id) und `session` (Sitzungsreferenz) sind
     * weitere Arten derselben Herkunft.
     *
     */
    public function voucherRedeemCheck(
        VoucherRedeemCheck $in,
        VoucherRedeemCheckActor $actor,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(VoucherRedeemCheckHandler::class, $in, $version, $actor)();
    }
}
