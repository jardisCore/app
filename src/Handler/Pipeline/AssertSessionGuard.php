<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Pipeline;

use JardisCore\App\Contract\SessionGuardInterface;
use JardisCore\App\Data\Route;
use LogicException;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Start-up check: with the switch on, every non-anonymous route must have a
 * {@see SessionGuardInterface} in its middleware chain, otherwise the app refuses to start.
 *
 * The chain of a route is `[...global, ...route middleware]`, exactly as
 * {@see RunMiddlewarePipeline} builds it. The check only asks whether a guard is part of
 * that list; it knows no order and no decorator around `RequireSession` - a wrapping
 * middleware that does not itself implement the marker does not count. No I/O.
 */
final class AssertSessionGuard
{
    public function __construct(private readonly bool $enabled)
    {
    }

    /**
     * @param list<Route> $routes
     * @param list<MiddlewareInterface> $globalMiddleware
     *
     * @throws LogicException when the switch is on and a non-anonymous route has no session guard
     */
    public function __invoke(array $routes, array $globalMiddleware): void
    {
        if (!$this->enabled) {
            return;
        }

        $unguarded = [];
        foreach ($routes as $route) {
            if ($route->anonymous || $this->hasGuard([...$globalMiddleware, ...$route->middleware])) {
                continue;
            }
            $unguarded[] = $route->method . ' ' . $route->path;
        }

        if ($unguarded === []) {
            return;
        }

        throw new LogicException(sprintf(
            'Routes without a session guard: %s. Add the "session" entry to the "middleware" list in '
            . 'bootstrap.php, or open the route with Routes::anonymous().',
            implode(', ', $unguarded)
        ));
    }

    /**
     * @param list<MiddlewareInterface> $chain
     */
    private function hasGuard(array $chain): bool
    {
        foreach ($chain as $middleware) {
            if ($middleware instanceof SessionGuardInterface) {
                return true;
            }
        }

        return false;
    }
}
