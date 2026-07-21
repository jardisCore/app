<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Response;

use Closure;
use JardisCore\App\Exception\UnresolvableHandlerResult;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Auto-mapping (E6): turns whatever a route/middleware handler returned
 * into a PSR-7 `ResponseInterface`.
 *
 * - `DomainResponseInterface` -> {@see MapDomainResponse} (the canonical
 *   envelope mapper, F4).
 * - `ResponseInterface` -> pass-through unchanged (a handler that already
 *   built its own PSR-7 response, e.g. `WrapCallableHandler` output).
 * - anything else -> `UnresolvableHandlerResult` is thrown; this is a
 *   handler programming error, not a client-facing case, so it is left to
 *   propagate to the outermost `HandleThrowable` (E8), which maps any
 *   uncaught Throwable to the generic 500 boundary response.
 */
final class ResolveResponse
{
    private readonly Closure $mapDomainResponse;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->mapDomainResponse = (new MapDomainResponse($responseFactory, $streamFactory))->__invoke(...);
    }

    public function __invoke(mixed $result): ResponseInterface
    {
        return match (true) {
            $result instanceof DomainResponseInterface => ($this->mapDomainResponse)($result),
            $result instanceof ResponseInterface => $result,
            default => throw new UnresolvableHandlerResult(sprintf(
                'Handler must return a DomainResponseInterface or a PSR-7 ResponseInterface, %s given.',
                get_debug_type($result),
            )),
        };
    }
}
