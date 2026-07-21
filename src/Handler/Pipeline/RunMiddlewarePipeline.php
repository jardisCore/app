<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Pipeline;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Builds the PSR-15 middleware chain from a global and a route-specific
 * middleware list plus the final route RequestHandlerInterface, then runs
 * it against one request (F3/E9).
 *
 * Ordering (E9, fixed by test): global middleware wraps route-specific
 * middleware - global is outer, route is inner. Within each group,
 * execution order equals registration order. The merged, outer-to-inner
 * list is therefore simply `[...global, ...route]`; folding it from the
 * end (Decorator-style, each middleware wraps the chain built so far)
 * yields a deterministic chain for any input.
 */
final class RunMiddlewarePipeline
{
    /**
     * @param list<MiddlewareInterface> $globalMiddleware
     * @param list<MiddlewareInterface> $routeMiddleware
     */
    public function __invoke(
        array $globalMiddleware,
        array $routeMiddleware,
        RequestHandlerInterface $finalHandler,
        ServerRequestInterface $request,
    ): ResponseInterface {
        $handler = $this->buildChain([...$globalMiddleware, ...$routeMiddleware], $finalHandler);

        return $handler->handle($request);
    }

    /**
     * @param list<MiddlewareInterface> $middleware ordered outer-first
     */
    private function buildChain(array $middleware, RequestHandlerInterface $finalHandler): RequestHandlerInterface
    {
        $next = $finalHandler;

        foreach (array_reverse($middleware) as $current) {
            $next = $this->wrap($current, $next);
        }

        return $next;
    }

    private function wrap(MiddlewareInterface $middleware, RequestHandlerInterface $next): RequestHandlerInterface
    {
        return new readonly class ($middleware, $next) implements RequestHandlerInterface {
            public function __construct(
                private MiddlewareInterface $middleware,
                private RequestHandlerInterface $next,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->next);
            }
        };
    }
}
