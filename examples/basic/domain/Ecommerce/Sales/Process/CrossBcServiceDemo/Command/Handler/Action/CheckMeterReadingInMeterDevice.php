<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\CrossBcServiceDemoContext;
use Ecommerce\Sales\Service\CheckMeterReadingInMeterDevice as ServiceCheckMeterReadingInMeterDevice;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;

/**
 * Node CheckMeterReadingInMeterDevice of the process CrossBcServiceDemo (call).
 *
 * Cross-Domain-Variante (D3): schreibt in die fremde BC Counter in der
 * fremden Domain MeterDevice ueber deren Prozess RecordCounterUpdate (G7),
 * nicht direkt ueber das Aggregat. Der Domain-Service uebersetzt die
 * eigene Eingabe in das fremde Prozess-Input-DTO (ACL).
 *
 * Calls the facade MeterDevice.Counter.RecordCounterUpdate, method recordCounterUpdate.
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
 * Knoten: CheckMeterReadingInMeterDevice
 */
final class CheckMeterReadingInMeterDevice extends EcommerceContext
{
    /**
     * @node-id a2b3c402
     * @throws Throwable
     */
    public function __invoke(CrossBcServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(ServiceCheckMeterReadingInMeterDevice::class)->__invoke($ctx);
    }
}
