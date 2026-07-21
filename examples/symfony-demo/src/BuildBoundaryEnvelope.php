<?php

declare(strict_types=1);

namespace SymfonyDemo;

use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * N3 illustration counterpart to `jardiscore/app`'s
 * `Handler/Response/BuildErrorResponse.php` (F9 boundary errors -- 404/405/
 * 500 -- answer in the SAME envelope as a domain-produced response,
 * `response-envelope.md` "Boundary errors" section). No domain code ran for
 * these, so there is no `DomainResponseInterface` to map; this builds the
 * envelope directly with `data`/`errors`/`meta` all empty ({}).
 */
final class BuildBoundaryEnvelope
{
    /**
     * @param list<string> $allowedMethods RFC 7231 Allow-header methods (405 only)
     */
    public function __invoke(int $status, array $allowedMethods = []): JsonResponse
    {
        $response = new JsonResponse([
            'status' => $status,
            'data' => new stdClass(),
            'errors' => new stdClass(),
            'meta' => new stdClass(),
        ], $status);

        if ($allowedMethods !== []) {
            $response->headers->set('Allow', implode(', ', $allowedMethods));
        }

        return $response;
    }
}
