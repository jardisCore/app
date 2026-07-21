<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Turns a Route's raw `Closure|RequestHandlerInterface` handler (E4) into a
 * PSR-15 `RequestHandlerInterface` ready for the middleware pipeline (F3),
 * folding in the auto-mapping Closure (`ResolveResponse`, E6) for the
 * Closure case: a route Closure may return either a `DomainResponseInterface`
 * (a BC's `process()`/read-facade result) or an already-built PSR-7
 * `ResponseInterface` - `ResolveResponse` turns either into the
 * `ResponseInterface` a `RequestHandlerInterface` must return.
 *
 * A `RequestHandlerInterface` handler is returned unchanged: PSR-15 already
 * guarantees its `handle()` returns a `ResponseInterface`, so no mapping
 * applies there - and it cannot be invoked as a plain callable in the first
 * place (unlike a Closure).
 */
final class ResolveRouteHandler
{
    private readonly Closure $wrapCallableHandler;

    /**
     * @param Closure(mixed): ResponseInterface $resolveResponse
     */
    public function __construct(private readonly Closure $resolveResponse)
    {
        $this->wrapCallableHandler = (new WrapCallableHandler())->__invoke(...);
    }

    public function __invoke(Closure|RequestHandlerInterface $handler): RequestHandlerInterface
    {
        if ($handler instanceof RequestHandlerInterface) {
            return $handler;
        }

        $resolveResponse = $this->resolveResponse;

        return ($this->wrapCallableHandler)(
            static fn (ServerRequestInterface $request): ResponseInterface => $resolveResponse($handler($request)),
        );
    }
}
