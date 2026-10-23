<?php

declare(strict_types=1);

namespace Ecommerce;

use JardisSupport\Contract\Kernel\AccessDeniedExceptionInterface;

/**
 * EcommerceAccessDenied: the denial of an access to an endpoint of the Ecommerce domain.
 *
 * Returned by EcommerceCheckAccess and thrown by the door that asked. It implements the
 * contract marker AccessDeniedExceptionInterface, so a door of the response layer answers it as
 * a 403 with the message key and the context. Hermetic - data only.
 */
final class EcommerceAccessDenied extends \RuntimeException implements AccessDeniedExceptionInterface
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
