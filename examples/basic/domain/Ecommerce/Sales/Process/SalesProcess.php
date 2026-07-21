<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process;

use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\BatchOrderFulfilment;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\BatchOrderFulfilmentHandler;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\CrossBcServiceDemoHandler;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoHandler;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchHandler;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\OrderInvoiceUnionAlertHandler;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\OrderInvoiceUnionAlert;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\RuleGuardedOrderIntakeHandler;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
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
     * und terminiert bei Erfolg (kein onSuccess-Edge). Nur auf onFail wird die
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
     * Rule-Knoten-/Vertrag-5-Fixture (docs/rules-layer/PLAN.md P5, A6):
     * demonstriert BEIDE Rules-Layer-Einsatzorte im selben Prozess.
     *
     * (1) Ein Rule-Knoten prueft frueh im Flow (vor teurer Arbeit, PRD §5
     * Frage 6) die Katalog-Rule OrderEligibleForProcessing — ein bewusst
     * UNGEBUNDENER Katalogeintrag (kein Endpunkt-Binding), der reine
     * Adapter-Nachweis (passed -> onSuccess, rejected -> onFail).
     *
     * (2) Erst danach ruft ein Custom-Knoten (Arbeits-Body, K2) den
     * Order-Aggregat-Command Order (createOrder) auf, dessen EIGENE
     * Rules.yaml-Kette (OrderIsCancellable, StockAvailable) real 422/500
     * liefern kann — Vertrag 5: 422 -> onFail (gemalte Kante), 5xx -> der
     * Exception-Pfad der Engine (keine onFail-Kante, kein stiller Erfolg).
     *
     */
    public function ruleGuardedOrderIntake(RuleGuardedOrderIntake $in, string $version = ''): DomainResponseInterface
    {
        return $this->context(RuleGuardedOrderIntakeHandler::class, $in, $version)();
    }
}
