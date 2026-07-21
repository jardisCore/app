<?php

declare(strict_types=1);

namespace SymfonyDemo;

use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * N3 illustration (PRD N3, PLAN P8 Block A): this class fulfils the exact
 * same client-facing contract as `jardiscore/app`'s `MapDomainResponse` /
 * `BuildErrorResponse` (`vendor/jardissupport/contracts/docs/response-envelope.md`)
 * -- WITHOUT importing `jardiscore/app`. It is re-implemented here, by hand,
 * against the framework-neutral contract document alone, using Symfony's
 * own `JsonResponse` instead of a PSR-7 response.
 *
 * Reproduces both documented deviations from the plain envelope shape:
 * - E2b Object-Coercion: an empty `data`/`errors`/`meta` array is coerced to
 *   a JSON object (`{}`), never left as `[]`.
 * - E2c 204-Exception: `NoContent` carries no body at all (HTTP-spec
 *   requirement), so this returns a bare `Response`, never a `JsonResponse`.
 */
final class MapDomainResponseToJsonResponse
{
    public function __invoke(DomainResponseInterface $response): Response
    {
        $status = ResponseStatus::from($response->getStatus());

        if ($status === ResponseStatus::NoContent) {
            return new Response(status: $status->value);
        }

        return new JsonResponse([
            'status' => $status->value,
            'data' => $this->coerce($response->getData()),
            'errors' => $this->coerce($response->getErrors()),
            'meta' => $this->coerce($response->getMetadata()),
        ], $status->value);
    }

    /**
     * @param array<string, mixed> $value
     * @return object|array<string, mixed>
     */
    private function coerce(array $value): object|array
    {
        return $value === [] ? new stdClass() : $value;
    }
}
