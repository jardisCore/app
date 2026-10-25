<?php

declare(strict_types=1);

namespace Ecommerce;

/**
 * EcommerceAccessDenied: the denial of an access to an endpoint of the Ecommerce domain.
 *
 * Returned by EcommerceCheckAccess and thrown by the door that asked. It carries no contract marker:
 * the right of an outer door is checked by the route (RequireRight of jardiscore/app answers 403
 * `access.rightMissing`), no exception type is answered 403 anymore. Hermetic - data only.
 */
final class EcommerceAccessDenied extends \RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly string $messageKey,
        private readonly array $context = [],
    ) {
        parent::__construct($messageKey);
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
