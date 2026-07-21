<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Event;

/**
 * Domain-Event SubjectRemovalAlerted — verkuendet von einem Event-Kasten ◇ im Prozess.
 *
 * Schlanke Identitaets-Fracht (D1/D8): die modell-aufgeloeste Identitaet des
 * betroffenen Aggregats + Standardfelder (eventId, occurredAt). Detaildaten
 * zieht der Konsument ueber die Lese-API (get{Agg}ById).
 */
final readonly class SubjectRemovalAlerted
{
    public function __construct(
        public string $removedOrderId,
        public string $eventId,
        public \DateTimeImmutable $occurredAt,
    ) {
    }
}
