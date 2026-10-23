<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Repository;

use JardisSupport\Contract\Kernel\ConcurrencyConflictExceptionInterface;

/**
 * Thrown by the generated Persist class when a conditional write hits
 * 0 rows: the row's read basis ($expected / snapshot in the WHERE) no
 * longer matches — another writer changed or removed it since this
 * aggregate was loaded. "Ehrlich schreiben": 0 Zeilen = lauter
 * Konflikt, nie Stille. The generated CommandHandler dispatch catches
 * this BEFORE the generic \Throwable branch and maps it to a 409
 * Conflict response carrying {reason, aggregate, context} — a
 * concurrency conflict is a fachliches Ereignis (the caller reloads
 * the complete aggregate and decides anew), not an unexpected
 * internal fault (500). Reasons:
 *  - 'stale_write': an UPDATE hit 0 rows (the read basis changed), or any
 *    write step of a create/update persist lost a race the database itself
 *    decided (snapshot/serialization conflict, deadlock — typed
 *    TransientConflictException of the repository package; repeat in a NEW
 *    transaction).
 *  - 'stale_delete': the root DELETE hit 0 rows (the aggregate vanished or
 *    changed), or a delete step lost such a database-decided race
 *    (TransientConflictException).
 *  - 'duplicate': an INSERT or UPDATE violated a unique constraint (typed
 *    UniqueViolationException of the repository package).
 * The context carries the outer-door identifier of the root (its outer
 * name; `id` only for an aggregate without a unique key), never FK or
 * child internals. `violated` names the outer-door fields a 'duplicate' hit
 * (root field, child `<kindProperty>.<field>`); it stays [] for 'stale_*'
 * and whenever the violated columns are unknown.
 *
 * It implements the contract marker ConcurrencyConflictExceptionInterface, so a
 * door of the response layer answers it as a 409 without knowing this class.
 */
final class ConcurrencyConflictException extends \RuntimeException implements ConcurrencyConflictExceptionInterface
{
    /**
     * @param array<string, mixed> $context
     * @param list<string> $violated
     */
    public function __construct(
        private readonly string $reason,
        private readonly string $aggregate,
        private readonly array $context = [],
        private readonly array $violated = [],
    ) {
        parent::__construct(sprintf(
            'Concurrency conflict (%s) on aggregate %s.',
            $reason,
            $aggregate
        ));
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getAggregate(): string
    {
        return $this->aggregate;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return list<string>
     */
    public function getViolated(): array
    {
        return $this->violated;
    }
}
