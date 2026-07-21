<?php

declare(strict_types=1);

namespace ExampleApp\Support;

use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;

/**
 * A tiny, immutable `DomainResponseInterface` implementation the example
 * app's own route handlers build directly — the same "any framework/any
 * caller can implement this Contract" property N3 relies on (F4/M3: the
 * DomainResponse-to-HTTP contract is framework-neutral, contract v2.1
 * `docs/response-envelope.md`). Used for two purposes:
 *
 * 1. POST /orders (F8, F6): the generated `RuleGuardedOrderIntakeHandler`
 *    never surfaces the created order's own 201/orderNumber in its own
 *    returned DomainResponse — verified empirically against the real
 *    fixture + MySQL (P7 diagnostic run), not assumed: its `PlaceOrder`
 *    action node's ON_FAIL edge routes to `OrderRejectedStub`, a
 *    deliberately unfilled "KI-/Dev-Hoheit" stub (see its own docblock)
 *    that unconditionally reports workflow success regardless of the
 *    real outcome, and even on the true happy path the handler only ever
 *    harvests Domain-scoped *events* from the workflow chain into its own
 *    result — never the `orderNumber` a node returned as its data. The
 *    route handler still only calls legitimate BC facades (`process()` to
 *    create, the read facade to confirm) — this VO is just the honest
 *    carrier for the 201-with-real-data response the PLAN's AK names.
 * 2. Synthetic status routes (204/400/401/403/409, PLAN P7 Block B): a
 *    thin handler exercising a status this generated fixture's own routes
 *    never produce builds one directly, honestly declared as synthetic.
 */
final readonly class StaticDomainResponse implements DomainResponseInterface
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors
     * @param array<string, mixed> $metadata
     * @param array<string, array<int, object>> $events
     */
    public function __construct(
        private int $status,
        private array $data = [],
        private array $errors = [],
        private array $metadata = [],
        private array $events = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status < 400;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getEvents(?EventScope $scope = null): array
    {
        return $this->events;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
