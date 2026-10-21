<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Response;

use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Response\Data\Response\DomainResponse;

/**
 * The 500 answer of a route whose request the generated adapter cannot build:
 * the named properties carry a nested collection or a non-scalar custom type
 * the contract does not model field-wise. The route stays registered so
 * contract and registration keep covering each other - implement it by hand
 * and replace the registration.
 *
 * Status `InternalError` (500), `data` `[]`, `errors`
 * `['routes' => ['Request property not buildable by the generated adapter: <a, b>']]`.
 */
final class AnswerUnsupportedRequest
{
    /**
     * @param list<string> $properties
     */
    public function __invoke(array $properties): DomainResponseInterface
    {
        return new DomainResponse(
            ResponseStatus::InternalError,
            [],
            [],
            ['routes' => ['Request property not buildable by the generated adapter: ' . implode(', ', $properties)]],
            [],
        );
    }
}
