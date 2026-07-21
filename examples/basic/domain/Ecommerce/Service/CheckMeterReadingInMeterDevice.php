<?php

declare(strict_types=1);

namespace Ecommerce\Service;

use DateTimeImmutable;
use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Sales;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Workflow\WorkflowResult;
use MeterDevice\Counter\Aggregate\Counter\Command\UpdateCounter;
use MeterDevice\Counter\Counter;
use MeterDevice\Counter\Process\RecordCounterUpdate\Command\RecordCounterUpdate;
use RuntimeException;
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
 */
final class CheckMeterReadingInMeterDevice extends EcommerceContext
{
    /**
     * Fuehrt den Cross-Domain-Write ausschliesslich ueber die Aussentuer des
     * fremden BC Counter aus: Schreiben ueber dessen Prozess
     * ($bc->process()->recordCounterUpdate()). Kein Aggregat-Direktzugriff (G7).
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): array
    {
        /** @var CrossBcServiceDemo $cmd */
        $cmd = $this->payload();

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
        return [
            'status' => $response->isSuccess() ? WorkflowResult::ON_SUCCESS : WorkflowResult::ON_FAIL,
            'data'   => ['counterIdentifier' => $cmd->counterIdentifier],
        ];
    }
}
