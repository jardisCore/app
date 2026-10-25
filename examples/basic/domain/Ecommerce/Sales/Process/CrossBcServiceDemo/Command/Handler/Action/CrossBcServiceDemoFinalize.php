<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\CrossBcServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node CrossBcServiceDemoFinalize of the process CrossBcServiceDemo (action).
 *
 * Platzhalter-Abschluss (onFail-Zweig) — bleibt bewusst ein Wurf-Stub
 * (kein consumedCall) — KI-/Dev-Hoheit.
 *
 * Mandant und Nutzer des Laufs: $ctx->input()->tenant, $ctx->input()->user (beide string).
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: CrossBcServiceDemo
 *     Cross-BC-Write-Fixture (bc-facade-layering PRD.md G7, P4, AK3-Teil 2): zwei
 *     Cross-BC-Call-Knoten belegen beide Ablageort-Faelle aus D3 —
 *     CheckStockInCatalog schreibt in eine fremde BC IM SELBEN Domain
 *     (Ecommerce.Sales -> Ecommerce.Catalog), CheckMeterReadingInMeterDevice
 *     schreibt in eine fremde BC in einer ANDEREN Domain (Ecommerce.Sales ->
 *     MeterDevice.Counter). D3 kollabiert auf denselben Ablageort — beide
 *     Service-Stubs landen in Domain/Ecommerce/Service/.
 *
 *     Ziele sind Prozesse, nicht Aggregate: Fremd-Writes laufen ueber den
 *     process() des fremden BC — Ecommerce.Catalog.UpdateProductInCatalog kapselt
 *     Product.UpdateProduct, MeterDevice.Counter.RecordCounterUpdate kapselt
 *     Counter.UpdateCounter. Die echte DTO-Uebersetzung lebt jetzt im
 *     Domain-Service (P4).
 *
 *     Happy Path: CheckStockInCatalog fuehrt den Cross-BC-Write auf Catalog aus
 *     und terminiert bei Erfolg — seit format-haertung A3 explizit per
 *     `terminal: true` deklariert (CrossBcCall ist laut Emissions-Matrix ein
 *     "formal freier" Knotentyp, den der Builder nicht automatisch erschoepfend
 *     fuellt; die Builder-Emission strictRouting verlangt sonst eine gemappte
 *     onTrue-Kante). Nur auf onFail wird die
 *     Cross-Domain-Variante als Ausweichpfad betreten; CrossBcServiceDemoFinalize
 *     bleibt der Fehler-Abschluss-Stub (KI-/Dev-Hoheit).
 * Knoten: CrossBcServiceDemoFinalize
 */
final class CrossBcServiceDemoFinalize extends EcommerceContext
{
    /**
     * @node-id a3b4c503
     */
    public function __invoke(CrossBcServiceDemoContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}
