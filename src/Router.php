<?php

declare(strict_types=1);

namespace JardisCore\App;

use Closure;
use JardisCore\App\Contract\RouterInterface;
use JardisCore\App\Data\Route;
use JardisCore\App\Data\RouteMatch;
use JardisCore\App\Handler\Routing\BuildDispatcher;
use JardisCore\App\Handler\Routing\DispatchRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Router orchestrator (E5), implements Contract\RouterInterface: consumes
 * the Route collection exactly once via BuildDispatcher (lazy) and
 * delegates every dispatch to DispatchRequest. No own logic - just
 * closure composition; FastRoute stays fully encapsulated inside
 * Handler/Routing/, this class never imports it (E3).
 */
final class Router implements RouterInterface
{
    private readonly Closure $dispatchFn;

    /**
     * @param list<Route> $routes
     */
    public function __construct(array $routes)
    {
        $buildDispatcher = (new BuildDispatcher())->__invoke(...);
        $dispatchRequest = (new DispatchRequest())->__invoke(...);
        $dispatcher = null;

        $this->dispatchFn = static function (ServerRequestInterface $request) use (
            &$dispatcher,
            $buildDispatcher,
            $dispatchRequest,
            $routes,
        ): RouteMatch {
            $dispatcher ??= $buildDispatcher($routes);

            return $dispatchRequest($dispatcher, $request);
        };
    }

    public function dispatch(ServerRequestInterface $request): RouteMatch
    {
        return ($this->dispatchFn)($request);
    }
}
