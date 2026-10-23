<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Response\Handler\Answer\AnswerOutcome;
use JardisSupport\Response\Handler\Answer\AnswerThrowable;
use JardisSupport\Response\Handler\Failure\LogFailure;
use Throwable;

/**
 * Door of the process CrossBcServiceDemo (hermetic): input check, run in its bracket (RunProcess), answer.
 *
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
 */
final class CrossBcServiceDemoHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        $definition = ($this->handle(CrossBcServiceDemoDefinition::class))();
        /** @var CrossBcServiceDemo $input */
        $input = $this->payload();
        $run = ($this->handle(RunProcess::class))($definition, $input, $this->depth());
        if ($run->failure !== null) {
            $answer = ($this->handle(AnswerThrowable::class))(
                $this->result(),
                $this->transformer(),
                $run->failure,
                $run->owner,
                $definition->getName()
            );
            ($this->handle(LogFailure::class, logger: $this->resource()->logger()))(
                $run->failure,
                $definition->getName(),
                $run->owner,
                $answer
            );

            return $answer;
        }

        return ($this->handle(AnswerOutcome::class))(
            $this->result(),
            $this->transformer(),
            $run->result,
            $definition->getName()
        );
    }
}
