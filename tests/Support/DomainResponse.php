<?php

declare(strict_types=1);

namespace JardisCore\App\Tests\Support;

use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;

/**
 * Contract-Fake (E15): a fully configurable `DomainResponseInterface`
 * implementation used to test `MapDomainResponse`/`ResolveResponse` against
 * every `ResponseStatus` case without going through a real kernel
 * `BoundedContext` call chain.
 */
final readonly class DomainResponse implements DomainResponseInterface
{
    /**
     * `$data` and `$errors` carry the SAME shape the implemented Contract
     * promises — both are keyed by context name, never flat. A fake that
     * declares them wider than the interface lets a test build a response
     * the real generated pipeline can never produce
     * (`ContextResponse::getData()` always returns `[$context => $data]`).
     *
     * @param array<string, array<string, mixed>> $data
     * @param array<string, array<int, string>> $errors
     * @param array<string, mixed> $metadata
     * @param array<string, array<int, object>> $events
     */
    public function __construct(
        private int $status = 200,
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
