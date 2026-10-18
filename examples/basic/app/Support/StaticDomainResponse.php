<?php

declare(strict_types=1);

namespace ExampleApp\Support;

use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;

/**
 * A tiny, immutable `DomainResponseInterface` implementation the example
 * app's own route handlers build directly - the same "any framework/any
 * caller can implement this Contract" property N3 relies on (F4/M3: the
 * DomainResponse-to-HTTP contract is framework-neutral, contract
 * `docs/response-envelope.md`). Used for two purposes:
 *
 * 1. POST /orders (F8, F6): the generated `RuleGuardedOrderIntakeHandler`
 *    answers a successful run with 200 and the data of its last node; a
 *    create answers 201 Created with the reloaded order, so the route
 *    handler carries the read facade's data under the status of its choice.
 * 2. Synthetic status routes (204/400/401/403/409, PLAN P7 Block B): a
 *    thin handler exercising a status this generated fixture's own routes
 *    never produce builds one directly, honestly declared as synthetic.
 */
final readonly class StaticDomainResponse implements DomainResponseInterface
{
    /**
     * `$data` is flat - the business object itself (generated responses
     * carry its type as `@type`), as the Contract promises; `$errors` is
     * keyed by context name.
     *
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>> $errors
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
