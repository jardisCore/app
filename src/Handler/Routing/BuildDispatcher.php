<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use JardisCore\App\Data\Route;

use function FastRoute\simpleDispatcher;
use function in_array;

/**
 * Builds the FastRoute dispatcher from the Route collection - consumed
 * exactly once (lazily, by Router). Registers HEAD automatically for every
 * GET route (E13, HTTP-conformant) unless an explicit HEAD route already
 * exists for the same path (no double registration).
 */
final class BuildDispatcher
{
    /**
     * @param list<Route> $routes
     */
    public function __invoke(array $routes): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $collector) use ($routes): void {
            $this->registerRoutes($collector, $routes);
            $this->registerAutoHead($collector, $routes);
        });
    }

    /**
     * @param list<Route> $routes
     */
    private function registerRoutes(RouteCollector $collector, array $routes): void
    {
        foreach ($routes as $route) {
            $collector->addRoute($route->method, $route->path, $route);
        }
    }

    /**
     * @param list<Route> $routes
     */
    private function registerAutoHead(RouteCollector $collector, array $routes): void
    {
        /** @var array<string, list<string>> $methodsByPath */
        $methodsByPath = [];
        foreach ($routes as $route) {
            $methodsByPath[$route->path][] = $route->method;
        }

        /** @var array<string, true> $headRegistered */
        $headRegistered = [];
        foreach ($routes as $route) {
            if ($route->method !== 'GET') {
                continue;
            }

            $alreadyExplicit = in_array('HEAD', $methodsByPath[$route->path], true);
            $alreadyAutoAdded = isset($headRegistered[$route->path]);

            if ($alreadyExplicit || $alreadyAutoAdded) {
                continue;
            }

            $collector->addRoute('HEAD', $route->path, $route);
            $headRegistered[$route->path] = true;
        }
    }
}
