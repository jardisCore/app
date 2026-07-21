<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Response;

use Closure;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The canonical `DomainResponseInterface` -> PSR-7 mapper (F4): the
 * reference implementation of `contract/docs/response-envelope.md`.
 *
 * Every one of the 11 `ResponseStatus` cases maps to its HTTP-code
 * equivalent (the enum is already backed 1:1 by the HTTP code -
 * `ResponseStatus::from()` both validates the value and yields it) and is
 * serialized as the `{status, data, errors, meta}` envelope via the same
 * canonical builder ({@see BuildErrorResponse}) the boundary-error cases
 * use - one place in the package assembles envelope bodies.
 *
 * Two documented deviations from the plain envelope shape:
 * - **E2c (204):** `NoContent` carries no body at all (HTTP-spec
 *   requirement) and, since there is no representation, no `Content-Type`
 *   header either - this mapper returns a bare empty response instead of
 *   calling the envelope builder.
 * - **422 pass-through:** `getData()` is serialized completely unchanged;
 *   for a `RuleViolation` this happens to be the `{rule, messageKey,
 *   context}` payload, but the mapper performs no reshaping of its own.
 *
 * `getEvents()` is deliberately NOT part of the client-facing envelope -
 * the Envelope Contract fixes exactly four top-level keys
 * (`status`/`data`/`errors`/`meta`); events are a domain-internal concern
 * (event bus / side-effect dispatch), never serialized to the HTTP client.
 */
final class MapDomainResponse
{
    private readonly Closure $buildEnvelope;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->buildEnvelope = (new BuildErrorResponse($responseFactory, $streamFactory))->__invoke(...);
    }

    public function __invoke(DomainResponseInterface $response): ResponseInterface
    {
        $status = ResponseStatus::from($response->getStatus());

        if ($status === ResponseStatus::NoContent) {
            return $this->responseFactory->createResponse($status->value);
        }

        return ($this->buildEnvelope)(
            $status->value,
            $response->getData(),
            $response->getErrors(),
            $response->getMetadata(),
        );
    }
}
