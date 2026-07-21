<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use stdClass;

/**
 * The ONE place that assembles a `{status, data, errors, meta}` JSON
 * envelope response (the Envelope Contract, `contract/docs/response-envelope.md`)
 * via injected PSR-17 factories only - never a hardwired concrete
 * implementation (E12-symmetric).
 *
 * Canonical for two callers:
 * - the boundary-error cases 400/404/405/500, which never reach domain
 *   code (F9: InvalidJsonBody, no matching route, wrong HTTP method,
 *   uncaught Throwable);
 * - {@see MapDomainResponse}, which delegates every domain-produced status
 *   (success and error alike) to this same builder so exactly one place in
 *   the package assembles envelope bodies (P4/P5 consolidation - this
 *   replaces P4's `Handler/Error/BuildErrorEnvelope.php`, now retired).
 *
 * Empty data/errors/meta arrays are coerced to a JSON object (E2b) instead
 * of an array. A non-empty `$allowedMethods` list adds the RFC-7231
 * `Allow` header (`, `-separated, e.g. `GET, POST`) - the 405 case; every
 * other caller simply omits it.
 */
final class BuildErrorResponse
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors
     * @param array<string, mixed> $meta
     * @param list<string> $allowedMethods RFC 7231 Allow-header methods (405 only)
     */
    public function __invoke(
        int $status,
        array $data = [],
        array $errors = [],
        array $meta = [],
        array $allowedMethods = [],
    ): ResponseInterface {
        $payload = [
            'status' => $status,
            'data' => $this->coerce($data),
            'errors' => $this->coerce($errors),
            'meta' => $this->coerce($meta),
        ];

        $json = json_encode($payload);
        if ($json === false) {
            $json = '{"status":500,"data":{},"errors":{},"meta":{}}';
        }

        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($json));

        if ($allowedMethods !== []) {
            $response = $response->withHeader('Allow', implode(', ', $allowedMethods));
        }

        return $response;
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
