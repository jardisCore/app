<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wraps a route handler as a PSR-15 RequestHandlerInterface (E4). Precedence:
 * a RequestHandlerInterface is checked first and returned as-is; a bare
 * Closure is wrapped into an anonymous RequestHandlerInterface
 * implementation that forwards the request to it.
 */
final class WrapCallableHandler
{
    public function __invoke(Closure|RequestHandlerInterface $handler): RequestHandlerInterface
    {
        if ($handler instanceof RequestHandlerInterface) {
            return $handler;
        }

        return new readonly class ($handler) implements RequestHandlerInterface {
            public function __construct(private Closure $handler)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return ($this->handler)($request);
            }
        };
    }
}
