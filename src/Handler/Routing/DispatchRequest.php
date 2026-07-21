<?php

declare(strict_types=1);

namespace JardisCore\App\Handler\Routing;

use FastRoute\Dispatcher;
use JardisCore\App\Data\Route;
use JardisCore\App\Data\RouteMatch;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Converts one FastRoute dispatch result into a RouteMatch VO (F1/F9). On
 * Found, delivers the matched Route plus its raw path parameters - applying
 * them as request attributes (F8a) is a deliberately separate
 * responsibility of the Pipeline/App call chain (P4/P6), the first place
 * that holds both the RouteMatch and the ServerRequestInterface at once;
 * DispatchRequest itself stays a pure matcher (SRP). On
 * MethodNotAllowed, delivers the full allowed-methods list for the path
 * (OPTIONS is never added automatically, E14).
 */
final class DispatchRequest
{
    public function __invoke(Dispatcher $dispatcher, ServerRequestInterface $request): RouteMatch
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        $result = $dispatcher->dispatch($method, $path);

        if ($result[0] === Dispatcher::FOUND) {
            /** @var Route $route */
            $route = $result[1];
            /** @var array<string, string> $parameters */
            $parameters = $result[2];

            return RouteMatch::found($route, $parameters);
        }

        if ($result[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var list<string> $allowedMethods */
            $allowedMethods = $result[1];

            return RouteMatch::methodNotAllowed($allowedMethods);
        }

        return RouteMatch::notFound();
    }
}
