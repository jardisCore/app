<?php

declare(strict_types=1);

namespace Ecommerce\Service;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;
use JardisSupport\Workflow\Data\Route;
use MeterDevice\Counter\Counter;
use MeterDevice\Counter\Model\Counter\Command\UpdateCounter;
use MeterDevice\Counter\Process\RecordCounterUpdate\Command\RecordCounterUpdate;
use Throwable;

/**
 * Cross-Domain-Variante (D3): schreibt in die fremde BC Counter in der
 * fremden Domain MeterDevice ueber deren Prozess RecordCounterUpdate (G7),
 * nicht direkt ueber das Aggregat. Der Domain-Service uebersetzt die
 * eigene Eingabe in das fremde Prozess-Input-DTO (ACL).
 *
 * Cross-BC-Ziel: MeterDevice.Counter.RecordCounterUpdate::recordCounterUpdate (RecordCounterUpdate).
 *
 * ACL-Hinweis: Antwort in eigenes Vokabular uebersetzen, fremdes DTO
 * nicht unveraendert durchreichen.
 *
 * Antwort: ein NodeResult (jardissupport/workflow). NodeResult::ok() bei Erfolg,
 * NodeResult::route(Route::OnFalse|OnTimeout|OnSkip, ...) fuer einen Zweig,
 * NodeResult::reject(<4xx-Status>, <messageKey>, <context>) fuer eine Ablehnung.
 * Die Fehlerform baut das Paket aus dem Kontext: 400 'fields', 409 'reason' /
 * 'aggregate' / 'violated', 422 'rule'; weitere Schluessel bleiben Kontext der Form.
 * Ein 5xx-Fall ist eine Exception, kein reject().
 */
final class CheckMeterReadingInMeterDevice extends EcommerceContext
{
    /**
     * Fuehrt den Cross-Domain-Write ausschliesslich ueber die Aussentuer des
     * fremden BC Counter aus: Schreiben ueber dessen Prozess
     * ($bc->process()->recordCounterUpdate()). Kein Aggregat-Direktzugriff (G7).
     *
     * @throws Throwable
     */
    public function __invoke(ProcessContext $context): NodeResult
    {
        /** @var CrossBcServiceDemo $cmd */
        $cmd = $context->input();

        /** @var Counter $counter */
        $counter = $this->handle(Counter::class);

        // ACL — die eigene Eingabe in das fremde Prozess-Input-DTO uebersetzen.
        $update = new RecordCounterUpdate(
            new UpdateCounter(
                counterIdentifier: $cmd->counterIdentifier,
                clientIdentifier: $cmd->clientIdentifier,
                meterLocationIdentifier: $cmd->meterLocationIdentifier,
                counterNumber: $cmd->counterNumber,
                activeFrom: new DateTimeImmutable($cmd->counterActiveFrom),
                activeUntil: null,
            )
        );

        // Write ueber den process() des fremden BC.
        /** @var DomainResponseInterface $response */
        $response = $counter->process()->recordCounterUpdate($update);

        // ACL — Antwort ins eigene Vokabular mappen; fremdes DTO nicht
        // unveraendert durchreichen.
        $answer = ['counterIdentifier' => $cmd->counterIdentifier];

        return $response->isSuccess() ? NodeResult::ok($answer) : NodeResult::route(Route::OnFalse, $answer);
    }
}
