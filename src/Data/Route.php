<?php

declare(strict_types=1);

namespace JardisCore\App\Data;

use Closure;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A single registered route (F1): HTTP method, path pattern, handler, and
 * its route-specific middleware. Handler is strictly
 * `Closure|RequestHandlerInterface` (E4 - a bare `callable` is not permitted
 * as a property type); Routes converts any callable to a Closure
 * immediately upon registration, so this VO never has to care about the
 * many shapes a PHP callable can take.
 */
final readonly class Route
{
    /**
     * @param list<MiddlewareInterface> $middleware route-specific middleware,
     *        in registration order; consumed by
     *        `Handler/Pipeline/RunMiddlewarePipeline` alongside the global
     *        middleware list to build the PSR-15 chain (E9)
     */
    public function __construct(
        public string $method,
        public string $path,
        public Closure|RequestHandlerInterface $handler,
        public array $middleware = [],
    ) {
    }
}
