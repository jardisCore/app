<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use Closure;
use JardisCore\App\Contract\RouterInterface;
use JardisCore\App\Data\Route;
use JardisCore\App\Data\RouteMatch;
use JardisCore\App\Data\RouteMatchStatus;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * The request-handling core chain (F1/F3/F8a/F9): the
 * `Closure(ServerRequestInterface): ResponseInterface` App hands to
 * `HandleThrowable` as its inner boundary.
 *
 * Dispatches the request via the injected `RouterInterface` (E3 - FastRoute
 * stays fully swappable), then either resolves a boundary error response
 * (F9: 404/405, the latter carrying the Allow header from
 * `RouteMatch::$allowedMethods`) or, on a Found match, applies the matched
 * path parameters as request attributes (F8a) before running the PSR-15
 * middleware pipeline (global outer, route inner, E9) around the route's
 * own handler (auto-mapped via `ResolveRouteHandler`, E6).
 */
final class DispatchAndRespond
{
    /**
     * @param Closure $applyRouteAttributes {@see ApplyRouteAttributes::__invoke()}
     * @param Closure $resolveRouteHandler {@see ResolveRouteHandler::__invoke()}
     * @param Closure $runMiddlewarePipeline {@see \JardisCore\App\Handler\Pipeline\RunMiddlewarePipeline::__invoke()}
     * @param Closure $buildErrorResponse {@see \JardisCore\App\Handler\Response\BuildErrorResponse::__invoke()}
     * @param list<MiddlewareInterface> $globalMiddleware
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly Closure $applyRouteAttributes,
        private readonly Closure $resolveRouteHandler,
        private readonly Closure $runMiddlewarePipeline,
        private readonly Closure $buildErrorResponse,
        private readonly array $globalMiddleware,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $match = $this->router->dispatch($request);

        return match ($match->status) {
            RouteMatchStatus::NotFound => ($this->buildErrorResponse)(404),
            // Positional call (not named args): PHPStan cannot map named
            // arguments onto a bare `Closure` type, so the empty
            // data/errors/meta fillers stay positional to reach the
            // allowedMethods slot (BuildErrorResponse::__invoke's 5th param).
            RouteMatchStatus::MethodNotAllowed => ($this->buildErrorResponse)(
                405,
                [],
                [],
                [],
                $match->allowedMethods,
            ),
            RouteMatchStatus::Found => $this->respondToFoundRoute($request, $match),
        };
    }

    private function respondToFoundRoute(ServerRequestInterface $request, RouteMatch $match): ResponseInterface
    {
        /** @var Route $route */
        $route = $match->route;

        $requestWithAttributes = ($this->applyRouteAttributes)($request, $match->parameters);
        $finalHandler = ($this->resolveRouteHandler)($route->handler);

        return ($this->runMiddlewarePipeline)(
            $this->globalMiddleware,
            $route->middleware,
            $finalHandler,
            $requestWithAttributes,
        );
    }
}
